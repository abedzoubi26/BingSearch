<?php

// Hooks for SavedResults and Search special pages.
class BingSearchHooks {

    // Update database schema by adding new table
    public static function onLoadExtensionSchemaUpdates(DatabaseUpdater $updater) {
        global $wgTableName;
        $updater->addExtensionTable($wgTableName, __DIR__ . '/../sql/saved_results.sql');
        return true;
    }

    // Add assets files to BingSearch SpecialPages.
    public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin) {
        $out->addModules('ext.bingSearch');
    }

    // Custom hook: on Save results.
    // params: search_result_item: array of search results items.
    // return: true|false
    public static function onSaveResults($search_result_item = []) {
        // Get user
//        print_r($search_result_item);die;
        global $wgUser, $wgTableName;
        try {

            // Get database.
            $db = wfGetDB(DB_MASTER);
            // get current time.
            $currentDateTs = wfTimestampNow();
            $queryData = [];
            // check if $search_result_item is array and not empty
            if (is_array($search_result_item) && !empty($search_result_item)) {
                // format selected search results.
                foreach ($search_result_item as $row) {
//                    print_r($row);die;
                    // check if checked attribute is set and comment text is not empty
                    if (isset($row["'checked'"]) && !empty($row["'comment'"])) {
                        $queryData[] = array(
                            'user_id' => $wgUser->getId(),
                            'title' => $row["'title'"],
                            'link' => $row["'link'"],
                            'description' => $row["'description'"],
                            'comment' => $row["'comment'"]
                        );
                    }
                }
            }

            // Bulk insert into table favorite.
            $db->insert($wgTableName, $queryData);
//            print_r($result);die;
            // Commit db changes.
            $db->commit();
            return true;
        } catch (DBError $exception) {
//            print_r($exception);die;
            return false;
        }
    }

    // Custom Hook
    // params: $outputPage, $link: redirect link.
    // results:  Add html to page output, true|false.
    public static function getSavedResults(OutputPage $outputPage, $link = null) {
        // Get user info.
        global $wgUser, $wgTableName;
        try {
            // Get mediawiki database.
            $db = wfGetDB(DB_MASTER);
            // Create select query.
            $results = $db->select($wgTableName, ['*'], array('user_id' => $wgUser->getId()), __METHOD__);

            if ($results->numRows() > 0) {
                $rows = [];

                while ($row = $results->next()) {
                    $rows[] = $row;
                }
                // render saved result items | Html elements.
                // Add html response to page output.
                $htmlResponse = self::renderSavedResults($rows);
                $outputPage->addHTML($htmlResponse);
            } else {
                // render empty results message | Html elements.
                // Add html response to page output.
                $htmlEmpty = self::emptySavedResult($link);
                $outputPage->addHTML($htmlEmpty);
            }
            return true;
        } catch (DBError $exception) {
            return false;
        }
    }

    // private function.
    // params: $data:array.
    // return: html elements | html empty results element.
    private static function renderSavedResults($data = []) {
        $links = [];
        $response = XML::openElement('div', ['class' => 'search-results']) .
                Xml::openElement('ul');

        try {
            // check if saved results items is array and not empty.
            if (isset($data) && !is_null($data) && is_array($data)) {

                // loop through all items and create html elements.
                foreach ($data as $index => $item) {
                    $links[] = $item->link;

                    $comment = "search_results[$index]['comment']";
                    $title = Html::hidden("search_results[$index]['title']", $item->title);
                    $description = Html::hidden("search_results[$index]['description']", $item->description);
                    $link = Html::hidden("search_results[$index]['id']", $item->id);

                    $response .=
                            Xml::openElement('li', ['data-id' => $item->id, 'class' => 'item']) .
                            XML::element('div', ['class' => 'search-result-item'], null) .
                            XML::element('h3', null, '') .
                            XML::element('a', ["href" => $item->link, "target" => "_blank"], $item->title) .
                            XML::closeElement('a') .
                            XML::closeElement('h3') .
                            XML::element('p', null, $item->description) .
                            XML::textarea($comment, $item->comment, 40, 5, ['class' => "comment-box"]) . $title . $description . $link .
                            // Add delete, update actions
                            XML::element('button', ['class' => 'delete-item'], wfMessage('delete-item')) .
                            XML::element('button', ['class' => 'update-item'], wfMessage('update-item')) .
                            XML::closeElement('div') .
                            Xml::closeElement('li');
                }
                $response .= Xml::closeElement('ul');
                $response .= XML::closeElement('div');

                $redirect = SpecialPage::getTitleFor('SavedResults')->getFullURL() . "?action=export_csv";
                $response .= Xml::element('a', ['href' => $redirect, 'class' => 'export-csv'], wfMessage('export-csv'));
                return $response;
            }
        } catch (DBUnexpectedError $exception) {
            return Xml::element('h3', wfMessage('no-data-found'));
        }
    }

    // private function.
    // params: $link
    // result: html elemet for empty list with redirect link.
    private static function emptySavedResult() {
        return Xml::openElement('div', ['class' => 'no-data']) .
                XML::element('a', ['href' => SpecialPage::getTitleFor('BingSearch')->getFullURL()], wfMessage('add')) .
                Xml::closeElement('div');
    }

    // Custom Hook
    // params
    // $data: array [link,link,link...]
    // $filename: default export.csv
    // $delimiter: default ,
    public static function exportCSV($data = [], $filename = "results.csv", $delimiter = "\n") {
        //headers
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        // open the "output" stream
        $f = fopen('php://output', 'w');
        fputcsv($f, ['links'], $delimiter);
        foreach ($data as $line) {
            fputcsv($f, $line, $delimiter);
        }
        // close file.
        fclose($f);

        exit;
    }

}
