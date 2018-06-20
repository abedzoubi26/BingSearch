<?php

// Special page: Allow user to search for any term.
class SpecialBingSearch extends SpecialPage {

    function __construct() {
        parent::__construct('BingSearch');
    }

    function execute($par) {

        $request = $this->getRequest();
        $output = $this->getOutput();
        $this->setHeaders();
        $this->addHelpLink('Help:Extension:BingSearch');

        // Get q request parameter
        $q = $this->getRequest()->getText('q');

        //replace any new line with space
        $q = str_replace("\n", " ", $q);

        // Render html search form.
        $searchForm = $this->renderForm($q);

        // Add form to page output.
        $this->getOutput()->addHTML($searchForm);

        // Check if the action is saved_data then run OnSaveResults hook [store selected links into d.b.].
        // Then redirect user to savedResults page
        if ($request->getText('action') == 'save_data') {
            Hooks::run('OnSaveResults', ["search_result_item" => $request->getArray('search_results')]);
            $redirect = SpecialPage::getTitleFor('SavedResults')->getLocalURL();
            $this->getOutput()->redirect($redirect);
            return;
        }

        // if q parameter is set and not empty, do the request from bing custom search api.
        // Add api response to page output.
        if (isset($q) && !empty($q)) {
            $bingApiResponse = $this->bingSearchApi($q);
            $output->addHTML($bingApiResponse);
        }
    }

    // parameters: $q:string.
    // result: html content: search form.
    private function renderForm($q) {
        $form = XML::openElement('div') .
                XML::openElement('form', array('method' => 'get', 'action' => $this->getPageTitle()->getLocalURL(), 'class' => 'bing-search-form')) .
                XML::openElement('div', ["class" => 'form-control text']) .
                XML::inputLabel(wfMessage('enter-your-query'), 'q', '', false, $q, ['type' => 'text', 'placeholder' => 'search']) .
                XML::submitButton('Go') .
                XML::closeElement('div') .
                HTML::hidden('action', 'search') .
                XML::closeElement('form') .
                XML::closeElement('div');

        return $form;
    }

    // parameters: $q: string
    // return: formatted html response | html error message.
    private function bingSearchApi($q) {
        global $wgBingSearchAccessKey, $wgBingSearchApi;
        try {
            // encode q
            $q = urlencode($q);


            // Prepare HTTP request
            // NOTE: Use the key 'http' even if you are making an HTTPS request. See:
            // http://php.net/manual/en/function.stream-context-create.php
            $headers = "Ocp-Apim-Subscription-Key: $wgBingSearchAccessKey\r\n";
            $options = array('http' => array(
                    'header' => $headers,
                    'method' => 'GET'));

            // Perform the Web request and get the JSON response
            $context = stream_context_create($options);
            $result = file_get_contents($wgBingSearchApi . "?q=" . urlencode($q), false, $context);
            $decodeApiResponse = json_decode($result);
            // format api json response to html.
            $htmlApiResponse = $this->searchResultFormat($decodeApiResponse);
            return $htmlApiResponse;
        } catch (DBUnexpectedError $e) {
            return Xml::element('h3', null, wfMessage('error-occured'));
        }
    }

    // params: data:stdObject. | data->items: array
    // return: html elements
    private function searchResultFormat($data) {
        $response = XML::openElement('div', ['class' => 'search-results']) .
                Xml::openElement('ul');

        try {
            // Check if data is set and $data->items is array and not null
            if (isset($data) && isset($data->webPages->value) && !is_null($data->webPages->value) && is_array($data->webPages->value)) {

                // Loop through all items and create html elements.
                foreach ($data->webPages->value as $index => $item) {
                    $textarea = "search_results[$index]['comment']";
                    $checkbox = "search_results[$index]['checked']";
                    $title = Html::hidden("search_results[$index]['title']", $item->name);
                    $description = Html::hidden("search_results[$index]['description']", $item->snippet);
                    $link = Html::hidden("search_results[$index]['link']", $item->url);
                    $response .=
                            Xml::openElement('li') .
                            Xml::check(
                                    $checkbox, false, ['value' => $index, 'class' => 'result-item', "autocomplete" => "off"]
                            ) .
                            XML::element('div', ['class' => 'search-result-item'], null) .
                            XML::element('h3', null, '') .
                            XML::element('a', ["href" => $item->url, "target" => "_blank"], $item->name) .
                            XML::closeElement('a') .
                            XML::closeElement('h3') .
                            XML::element('a', ["href" => $item->url, "target" => "_blank"], $item->url) . XML::closeElement('a') .
                            XML::element('p', null, $item->snippet) .
                            XML::textarea($textarea, '', 40, 5, ['class' => "comment-box hidden", 'placeholder' => wfMessage('insert-comment')]) . $title . $description . $link .
                            XML::closeElement('div') .
                            Xml::closeElement('li');
                }
                $response .= Xml::closeElement('ul');
                $response .= XML::closeElement('div');

                // Add action parameter with save_data value.
                $response.= Xml::openElement('div', null) .
                        Html::hidden('action', 'save_data') .
                        XML::element('button', ['type' => 'submit', 'class' => 'save-results'], wfMessage('save')) .
                        Xml::closeElement('div');
                $response = Xml::tags('form', array('method' => 'post', 'action' => $this->getPageTitle()->getLocalURL()), $response);

                return $response;
            } else if (isset($data) && isset($data->error) && !empty($data->error->errors)) {
                return Xml::element('h3', null, wfMessage('error-occured'));
            } else {
                return XML::element('h2', null, wfMessage('no-results'));
            }
        } catch (DBError $exception) {
            return Xml::element('h3', null, wfMessage('error-occured'));
        }
    }

    // Set a group name for this page.
    protected function getGroupName() {
        return 'bing_group';
    }

}

