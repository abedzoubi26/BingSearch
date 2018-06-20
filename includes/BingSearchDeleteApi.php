<?php

// Custom MediaWiki API for ajax request.
// delete saved item for DB.
class BingSearchDeleteApi extends ApiBase {

    public function execute() {
        // Get user info.
        global $wgUser, $wgTableName;
        try {
            // get item id parameter "primary key".
            $item_id = $this->getParameter('id');
            // execute delete query where id= id parameter and user_id = loggedIn user id.
            $queryResult = $this->getDB()->delete($wgTableName, ["user_id" => $wgUser->getId(), "id" => $item_id]);
            // Get number of affcted rows
            $affectedRows = $this->getDB()->affectedRows();

            // return results [status 1 => success, 0: failed]
            if ($queryResult && $affectedRows > 0)
                $this->getResult()->addValue(null, 'response', ["status" => 1]);
            else
                $this->getResult()->addValue(null, 'response', ["status" => 0]);
        } catch (DBUnexpectedError $e) {
            $this->getResult()->addValue(null, 'response', ["status" => 0]);
        }
    }

    // Check api allowed parameters + Validation.
    public function getAllowedParams() {
        return array(
            'id' => array(
                ApiBase::PARAM_TYPE => 'integer',
                ApiBase::PARAM_REQUIRED => true
            )
        );
    }

}
