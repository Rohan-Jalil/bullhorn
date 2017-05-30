<?php

/**
 * Created by PhpStorm.
 * Date: 5/19/17
 * Time: 8:04 PM
 */

namespace NorthCreek\Bullhorn\Dispatcher;

use NorthCreek\Bullhorn\Bullhorn;
use NorthCreek\Bullhorn\Extractor\BullhornCandidateExtractor;

class BullhornCandidateDispatcher extends Bullhorn
{
    /**
     * @param $data
     *
     * @return mixed
     */
    public function submitNote($data)
    {
        $this->authenticate();

        $response = $this->makeCall( $this->getBaseUrl()."entity/Note", json_encode($data['notes']), 'PUT');

        return $response;
    }

    /**
     * @param $reference
     *
     * @return null
     */
    public function submitReference($reference)
    {
        $this->authenticate();

        $response = $this->makeCall( $this->getBaseUrl()."entity/CandidateReference", json_encode($reference), 'PUT');

        if (array_key_exists("changedEntityId", $response)) {

            return $response['changedEntityId'];

        }

        return null;

    }

    /**
     * @param $candidate
     *
     * @return mixed
     */
    public function createCandidate($candidate)
    {
        $this->authenticate();

        $response = $this->makeCall( $this->getBaseUrl()."/entity/Candidate", $candidate, 'PUT');

        if (array_key_exists('errorMessage', $response)) {
            // "Candidate creation failed with problem ".$decoded['errorMessage'];
        } else {
            $newId = $response['changedEntityId'];
        }

        return $response;
    }

    /**
     * @param $candidate
     *
     * @return mixed
     */
    public function updateCandidate($candidate) {

        $this->authenticate();

        $id = $candidate['id'];

        $response = $this->makeCall( $this->getBaseUrl()."entity/Candidate/".$id, $candidate, 'POST');

        if (!array_key_exists('errorMessage', $response)) {
            //success
        } else {
            // "Candidate $id update failed with problem ".$decoded['errorMessage'];
        }

        return $response;
    }

    /**
     * @param $candidate
     * @param $filename
     * @param $body
     * @param string $type
     *
     * @return mixed
     */
    public function submitFileAsString($candidate, $filename, $body, $type='To Be Checked')
    {
        $this->authenticate();

        $file_base64 = base64_encode($body);

        $id = $candidate["id"];

        $data = json_encode([
          'fileContent' => $file_base64,
          'externalID' => 'Portfolio',
          'name' => $filename,
          'fileType' => 'SAMPLE',
          'description' => 'associated file',
          'type' => $type
        ]);

        $response = $this->makeCall( $this->getBaseUrl()."file/Candidate/$id", $data, 'PUT');

        return $response;
    }
}