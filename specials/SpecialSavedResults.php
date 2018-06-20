<?php

// Special Page: Show saved results.
class SpecialSavedResults extends SpecialPage {

    function __construct() {
        parent::__construct('SavedResults');
    }

    function execute($par) {
        // Check permissions <execute>.
        // Users should be logged-in.
        // Otherwise: Redirect to login page.
        if (!$this->getUser()->isAllowed('execute')) {
            $this->requireLogin();
            return;
        }

        if ($this->getRequest()->getText('action') == 'export_csv') {
            Hooks::run("onExportCSV", [array($this->getLinksFromDB())]);
            return;
        }

        $output = $this->getOutput();
        $this->setHeaders();

        // Change page title.
        $this->getOutput()->setPageTitle('Saved Results');

        // Run onLoadSearchResults hook which should print saved results.
        Hooks::run('onLoadSearchResults', [$output, '']);
    }

    //params: no parameters
    // return: array of saved links | false.
    private function getLinksFromDB() {
        global $wgUser,$wgTableName;
        try {

            $db = wfGetDB(DB_MASTER);
            $rows = [];
            $results = $db->select($wgTableName, ['link'], array('user_id' => $wgUser->getId()), __METHOD__);            
            if ($results->numRows() > 0) {
                while ($row = $results->next()) {
                    $rows[] = $row->link;
                }
            }
            return $rows;
        } catch (DBError $exception) {
            return false;
        }
    }

    // Set a group name for this page.
    protected function getGroupName() {
        return 'bing_group';
    }

}
