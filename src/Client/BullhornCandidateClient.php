<?php

/**
 * Created by PhpStorm.
 * Date: 5/24/17
 * Time: 11:04 PM
 */

namespace NorthCreek\Bullhorn\Client;

use NorthCreek\Bullhorn\Dispatcher\BullhornCandidateDispatcher;
use NorthCreek\Bullhorn\Extractor\BullhornCandidateExtractor;
use NorthCreek\Bullhorn\BullhornFactory;

class BullhornCandidateClient
{

    private $_dispatcher;
    private $_extractor;

    /**
     * @return BullhornCandidateDispatcher
     */
    public function getDispatcher ()
    {
        return $this->_dispatcher;
    }

    /**
     * @param BullhornCandidateDispatcher $dispatcher
     */
    public function setDispatcher (BullhornCandidateDispatcher $dispatcher)
    {
        $this->_dispatcher = $dispatcher;
    }

    /**
     * @return BullhornCandidateExtractor
     */
    public function getExtractor ()
    {
        return $this->_extractor;
    }

    /**
     * @param BullhornCandidateExtractor $extractor
     */
    public function setExtractor (BullhornCandidateExtractor $extractor)
    {
        $this->_extractor = $extractor;
    }

    /**
     * BullhornCandidateClient constructor.
     */
    public function __construct ()
    {
        $this->_dispatcher = (new BullhornFactory())->makeDispatcher('candidate');
        $this->_extractor = (new BullhornFactory())->makeExtractor('candidate');
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public function submit($data) {

        $response = [];

        $userId = $this->_extractor->findByEmail($data['email']);
        $userId = is_array($userId) ? current($userId) : $userId;

        if ($userId) {

            $response = $this->_dispatcher->updateCandidate($data);

        } else {

            $response = $this->_dispatcher->createCandidate($data);

        }

        if (array_key_exists("changedEntityId", $response)) {

            $this->submitReferences($data);

            $this->_dispatcher->submitNote($data);

        }

        return $response;
    }

    /**
     * @param $data
     *
     * @return null
     */
    public function submitReferences($data)
    {
        if ($data)
        {
            if (is_array($data['references']))
            {
                foreach($data['references'] as $reference)
                {
                    $referenceData = $this->_extractor->findCandidateReference($reference['candidate']['id']);

                    $found = false;

                    foreach ($referenceData as $rd) {

                        if ($reference['referenceFirstName'] == $rd['referenceFirstName'] &&
                          $reference['referenceLastName'] == $rd['referenceLastName'])
                        {
                            $found = true;
                            break;
                        }

                    }

                    if (! $found)
                    {

                        $this->_dispatcher->submitReference($reference);

                    }

                }
            }
        }
    }

    /**
     * @param $candidate
     * @param $filename
     * @param $body
     * @param $type
     */
    public function submitFileAsString($candidate, $filename, $body, $type)
    {
        $this->_dispatcher->submitFileAsString($candidate, $filename, $body, $type);
    }

}