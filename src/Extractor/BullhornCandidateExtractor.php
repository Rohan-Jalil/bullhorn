<?php

/**
 * Created by PhpStorm.
 * Date: 5/19/17
 * Time: 8:03 PM
 */

namespace NorthCreek\Bullhorn\Extractor;

use NorthCreek\Bullhorn\Bullhorn;

class BullhornCandidateExtractor extends Bullhorn
{

    /**
     * @param $email
     *
     * @return array
     */
    public function findByEmail($email) {

        $this->authenticate();

        return $this->findByField("email", $email);
    }

    /**
     * @param $field
     * @param $value
     *
     * @return array
     */
    public function findByField($field, $value) {
        return $this->findByQuery("$field:$value");
    }

    /**
     * @param $query
     *
     * @return array
     */
    public function findByQuery($query) {

        $this->authenticate();

        $uri = $this->getService()->getSearchQueryCandidateUri($this->getBaseUrl(), $this->getSessionKey(), $query);

        $client = $this->getHttpClient();
        $response = $client->retrieveResponse($uri, '', [], 'GET');
        $response = $this->extractJson($response);

        $ids = [];
        if (array_key_exists("data", $response)) {
            $list_of_ids = $response["data"];
            foreach($list_of_ids as $idHolder) {
                $id = $idHolder["id"];
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function findCandidateReference($id) {

        $url = $this->getBaseUrl()."query/CandidateReference";

        $fieldList = 'id,referenceFirstName,referenceLastName,companyName,referenceTitle,referencePhone,referenceEmail,customTextBlock1,isDeleted,candidate';

        $uri = $this->getService()->getRestUri($url, $this->getSessionKey(),
          [
            'fields'=>$fieldList,
            'where'=>'candidate.id='.$id.' AND isDeleted=false'
          ]
        );

        $query = $this->getHttpClient()->retrieveResponse($uri, '', [], 'GET');
        $response = $this->extractJson($query);

        return $response['data'];
    }
}