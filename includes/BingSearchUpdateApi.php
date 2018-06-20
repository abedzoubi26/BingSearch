<?php

// Custom MediaWiki API for ajax request.
// update saved item D.B.
class BingSearchUpdateApi extends ApiBase {

    public function execute() {
        // Get user info.
        global $wgUser, $wgTableName;
        try {
            // get item id parameter "primary key".
            $item_id = $this->getParameter('id');
            // get item comment parameter.
            $item_comment = $this->getParameter('comment');
            // Execute update query set comment = comment parameter value where user_id = logged-in user id
            $queryResult = $this->getDB()->update($wgTableName, ['comment' => $item_comment], ["user_id" => $wgUser->getId(), "id" => $item_id]);
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
            ),
            'comment' => array(
                ApiBase::PARAM_TYPE => 'text',
                ApiBase::PARAM_REQUIRED => true
            )
        );
    }

}
