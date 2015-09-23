<?php

namespace LinguaLeo\EmarsysApiClient;

use LinguaLeo\EmarsysApiClient\Exceptions\ClientException;
use LinguaLeo\EmarsysApiClient\Exceptions\ServerException;
use LinguaLeo\EmarsysApiClient\Transport\HttpTransportInterface;

/**
 * Class Client
 * @package LinguaLeo\EmarsysApiClient
 */
class Client
{
    const EMAIL_STATUS_IN_DESIGN = 1;
    const EMAIL_STATUS_TESTED = 2;
    const EMAIL_STATUS_LAUNCHED = 3;
    const EMAIL_STATUS_READY = 4;
    const EMAIL_STATUS_DEACTIVATED = -3;

    const LAUNCH_STATUS_NOT_LAUNCHED = 0;
    const LAUNCH_STATUS_IN_PROGRESS = 1;
    const LAUNCH_STATUS_SCHEDULED = 2;
    const LAUNCH_STATUS_ERROR = -10;

    /**
     * Base API URL
     * For instance, "https://suite9.emarsys.net/api/v2/"
     *
     * @var string
     */
    private $baseUrl;

    /**
     * Emarsys API user name
     *
     * @var string
     */
    private $username;

    /**
     * Emarsys API password
     *
     * @var string
     */
    private $secret;

    /**
     * @var HttpTransportInterface
     */
    private $transport;

    /**
     * @var array
     */
    private $fieldsMapping = [];

    /**
     * @var array
     */
    private $choicesMapping = [];

    /**
     * @var array
     */
    private $systemFields = ['key_id'];

    /**
     * @param HttpTransportInterface $transport HTTP client implementation
     * @param string $username The username requested by the Emarsys API
     * @param string $secret The secret requested by the Emarsys API
     * @param string $baseUrl Your API baseUrl, such as "https://suite9.emarsys.net/api/v2/"
     * @param array $fieldsMap Overrides the default fields mapping if needed
     * @param array $choicesMap Overrides the default choices mapping if needed
     */
    public function __construct(
        HttpTransportInterface $transport,
        $username,
        $secret,
        $baseUrl,
        $fieldsMap = [],
        $choicesMap = []
    ) {
        $this->transport = $transport;
        $this->username = $username;
        $this->secret = $secret;
        $this->fieldsMapping = $fieldsMap;
        $this->choicesMapping = $choicesMap;
        $this->baseUrl = $baseUrl;

        if (empty($this->fieldsMapping)) {
            $this->fieldsMapping = $this->parseIniFile('fields.ini');
        }

        if (empty($this->choicesMapping)) {
            $this->choicesMapping = $this->parseIniFile('choices.ini');
        }
    }

    /**
     * Add your custom fields mapping
     * This is useful if you want to use string identifiers instead of ids when you play with contacts fields
     *
     * Example:
     *  $mapping = array(
     *      'myCustomField' => 7147,
     *      'myCustomField2' => 7148,
     *  );
     *
     * @param array $mapping
     */
    public function addFieldsMapping($mapping = [])
    {
        $this->fieldsMapping = array_merge($this->fieldsMapping, $mapping);
    }

    /**
     * Add your custom field choices mapping
     * This is useful if you want to use string identifiers instead of ids when you play with contacts field choices
     *
     * Example:
     *  $mapping = array(
     *      'myCustomField' => array(
     *          'myCustomChoice' => 1,
     *          'myCustomChoice2' => 2,
     *      )
     *  );
     *
     * @param array $mapping
     */
    public function addChoicesMapping($mapping = [])
    {
        foreach ($mapping as $field => $choices) {
            if (is_array($choices)) {
                if (!array_key_exists($field, $this->choicesMapping)) {
                    $this->choicesMapping[$field] = [];
                }

                $this->choicesMapping[$field] = array_merge($this->choicesMapping[$field], $choices);
            }
        }
    }

    /**
     * Returns a field id from a field name (specified in the fields mapping)
     *
     * @param string $field
     * @return int
     * @throws Exceptions\ClientException
     */
    public function getFieldId($field)
    {
        if (in_array($field, $this->systemFields)) {
            return $field;
        }

        if (!isset($this->fieldsMapping[$field])) {
            throw new ClientException(sprintf('Unrecognized field name "%s"', $field));
        }

        return (int)$this->fieldsMapping[$field];
    }

    /**
     * Returns a field name from a field id (specified in the fields mapping) or the field id if no mapping is found
     *
     * @param int $fieldId
     * @return string|int
     */
    public function getFieldName($fieldId)
    {
        $fieldName = array_search($fieldId, $this->fieldsMapping);

        if ($fieldName) {
            return $fieldName;
        }

        return $fieldId;
    }

    /**
     * Returns a choice id for a field from a choice name (specified in the choices mapping)
     *
     * @param string|int $field
     * @param string|int $choice
     * @throws Exceptions\ClientException
     * @return int
     */
    public function getChoiceId($field, $choice)
    {
        $fieldName = $this->getFieldName($field);

        if (!array_key_exists($fieldName, $this->choicesMapping)) {
            throw new ClientException(sprintf('Unrecognized field "%s" for choice "%s"', $field, $choice));
        }

        if (!isset($this->choicesMapping[$fieldName][$choice])) {
            throw new ClientException(sprintf('Unrecognized choice "%s" for field "%s"', $choice, $field));
        }

        return (int)$this->choicesMapping[$fieldName][$choice];
    }

    /**
     * Returns a choice name for a field from a choice id (specified in the choices mapping) or the choice id if no
     * mapping is found
     *
     * @param string|int $field
     * @param int $choiceId
     * @throws Exceptions\ClientException
     * @return string|int
     */
    public function getChoiceName($field, $choiceId)
    {
        $fieldName = $this->getFieldName($field);

        if (!array_key_exists($fieldName, $this->choicesMapping)) {
            throw new ClientException(sprintf('Unrecognized field "%s" for choice id "%s"', $field, $choiceId));
        }

        $field = array_search($choiceId, $this->choicesMapping[$fieldName]);

        if ($field) {
            return $field;
        }

        return $choiceId;
    }

    /**
     * Returns a list of condition rules.
     *
     * @return Response
     */
    public function getConditions()
    {
        return $this->send(HttpTransportInterface::METHOD_GET, 'condition');
    }

    /**
     * Creates one new contact/recipients.
     * Example :
     *  $data = array(
     *      'key_id' => '3',
     *      '3' => 'recipient@example.com',
     *      'source_id' => '123',
     *  );
     * @param array $data
     * @return Response
     */
    public function createContact(array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_POST, 'contact', $this->mapFieldsToIds($data));
    }

    /**
     * Creates multiple new contacts/recipients.
     * Example :
     *  $data = array(
     *      'key_id' => '3',
     *      'contacts' => array(
     *          array(
     *              '3' => 'recipient@example.com',
     *              'source_id' => '123',
     *          ),
     *          array(
     *              '3' => 'recipient2@example.com',
     *              'source_id' => '321',
     *          ),
     *      )
     *  );
     *
     * @param array $data
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function createMultipleContacts(array $data)
    {
        if (!isset($data['contacts'])) {
            throw new ClientException("Missing `contacts` parameter in request");
        }

        foreach ($data['contacts'] as &$contactData) {
            $contactData = $this->mapFieldsToIds($contactData);
        }

        return $this->send(HttpTransportInterface::METHOD_POST, 'contact', $data);
    }

    /**
     * Updates one contact/recipient, identified by an external ID.
     *
     * @param array $data
     * @return Response
     */
    public function updateContact(array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_PUT, 'contact', $this->mapFieldsToIds($data));
    }

    /**
     * Updates multiple contacts/recipients.
     * Example :
     *  $data = array(
     *      'key_id' => '3',
     *      'contacts' => array(
     *          array(
     *              '3' => 'recipient@example.com',
     *              'source_id' => '123',
     *          ),
     *          array(
     *              '3' => 'recipient2@example.com',
     *              'source_id' => '321',
     *          ),
     *      )
     *  );
     *
     * @param array $data
     * @throws ClientException
     * @throws ServerException
     * @return Response
     */
    public function updateMultipleContacts(array $data)
    {
        if (!isset($data['contacts'])) {
            throw new ClientException("Missing `contacts` parameter in request");
        }

        foreach ($data['contacts'] as &$contactData) {
            $contactData = $this->mapFieldsToIds($contactData);
        }

        return $this->send(HttpTransportInterface::METHOD_PUT, 'contact', $data);
    }

    /**
     * Returns the internal ID of a contact specified by its external ID.
     *
     * @param string $fieldId
     * @param string $fieldValue
     * @throws Exceptions\ClientException
     * @return Response
     */
    public function getContactId($fieldId, $fieldValue)
    {
        $response = $this->send(HttpTransportInterface::METHOD_GET, sprintf('contact/%s=%s', $fieldId, $fieldValue));

        $data = $response->getData();

        if (isset($data['id'])) {
            return $data['id'];
        }

        throw new ClientException('Missing "id" in response');
    }

    /**
     * Exports the selected fields of all contacts with properties changed in the time range specified.
     *
     * @param array $data
     * @return Response
     */
    public function getContactChanges(array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_POST, 'contact/getchanges', $data);
    }

    /**
     * Returns the list of emails sent to the specified contacts.
     *
     * @param array $data
     * @return Response
     */
    public function getContactHistory(array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_POST, 'contact/getcontacthistory', $data);
    }

    /**
     * Returns all data associated with a contact.
     *
     * Example:
     *
     *  $data = array(
     *      'keyId' => 3, // Contact element used as a key to select the contacts.
     *                    // To use the internalID, pass "id" to the "keyId" parameter.
     *      'keyValues' => array('example@example.com', 'example2@example.com') // An array of contactIDs or values of
     *                                                                          // the column used to select contacts.
     *  );
     *
     * @param array $data
     * @return Response
     */
    public function getContactData(array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_POST, 'contact/getdata', $data);
    }

    /**
     * Exports the selected fields of all contacts which registered in the specified time range.
     *
     * @param array $data
     * @return Response
     */
    public function getContactRegistrations(array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_POST, 'contact/getregistrations', $data);
    }

    /**
     * Returns a list of contact lists which can be used as recipient source for the email.
     *
     * @param array $data
     * @return Response
     */
    public function getContactList(array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_GET, 'contactlist', $data);
    }

    /**
     * Creates a contact list which can be used as recipient source for the email.
     *
     * @param array $data
     * @return Response
     */
    public function createContactList(array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_POST, 'contactlist', $data);
    }

    /**
     * Creates a contact list which can be used as recipient source for the email.
     *
     * @param string $listId
     * @param array $data
     * @return Response
     */
    public function addContactsToContactList($listId, array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_POST, sprintf('contactlist/%s/add', $listId), $data);
    }

    /**
     * This deletes contacts from the contact list which can be used as recipient source for the email.
     *
     * @param string $listId
     * @param array $data
     * @return Response
     */
    public function removeContactsFromContactList($listId, array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_POST, sprintf('contactlist/%s/delete', $listId), $data);
    }

    /**
     * Returns a list of field values
     * using $data as query
     *
     * Example :
     *  $data = array(
     *      '<field_id>' => <field_value>,
     *      'limit' => '100',
     *      'offset' => '0',
     *      'excludeempty' => true,
     *  );
     *
     * @param int|string $fieldId
     * @param array $data
     * @return Response
     * @throws ServerException
     */
    public function listingContacts($fieldId, array $data = [])
    {
        return $this->send(
            HttpTransportInterface::METHOD_GET,
            sprintf(
                'contact/query/return=%d%s',
                $this->getFieldId($fieldId),
                $data ? '&'.http_build_query($data) : ''
            ),
            $data
        );
    }

    /**
     * Returns a list of emails.
     *
     * @param int|null $status
     * @param int|null $contactList
     * @return Response
     */
    public function getEmails($status = null, $contactList = null)
    {
        $data = [];
        if (null !== $status) {
            $data['status'] = $status;
        }
        if (null !== $contactList) {
            $data['contactlist'] = $contactList;
        }
        $url = 'email';
        if (count($data) > 0) {
            $url = sprintf('%s/%s', $url, http_build_query($data));
        }

        return $this->send(HttpTransportInterface::METHOD_GET, $url);
    }

    /**
     * Creates an email in eMarketing Suite and assigns it the respective parameters.
     * Example :
     *  $data = array(
     *      'language' => 'en',
     *      'name' => 'test api 010',
     *      'fromemail' => 'sender@example.com',
     *      'fromname' => 'sender email',
     *      'subject' => 'subject here',
     *      'email_category' => '17',
     *      'html_source' => '<html>Hello $First Name$,... </html>',
     *      'text_source' => 'email text',
     *      'segment' => 1121,
     *      'contactlist' => 0,
     *      'unsubscribe' => 1,
     *      'browse' => 0,
     *  );
     *
     * @param array $data
     * @return Response
     */
    public function createEmail(array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_POST, 'email', $data);
    }

    /**
     * Updates an email in eMarketing Suite and assigns it the respective parameters.
     * Example :
     *  $data = array(
     *      'language' => 'en',
     *      'name' => 'test api 010',
     *      'subject' => 'subject here',
     *      'html_source' => '<html>Hello $First Name$,... </html>',
     *      'text_source' => 'email text',
     *      'additional_linktracking_parameters' => '',
     *  );
     *
     * @param string $emailId
     * @param array $data
     * @return Response
     */
    public function updateEmail($emailId, array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_POST, sprintf('email/%s/patch', $emailId), $data);
    }

    /**
     * Returns the attributes of an email and the personalized text and HTML source.
     *
     * @param string $emailId
     * @param array $data
     * @return Response
     */
    public function getEmail($emailId, array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_GET, sprintf('email/%s', $emailId), $data);
    }

    /**
     * Launches an email. This is an asynchronous call, which returns 'OK' if the email is able to launch.
     *
     * @param string $emailId
     * @param array $data
     * @return Response
     */
    public function launchEmail($emailId, array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_POST, sprintf('email/%s/launch', $emailId), $data);
    }

    /**
     * Returns the HTML or text version of the email either as content type 'application/json' or 'text/html'.
     *
     * @param string $emailId
     * @param array $data
     * @return Response
     */
    public function previewEmail($emailId, array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_POST, sprintf('email/%s/launch', $emailId), $data);
    }

    /**
     * Returns the summary of the responses of a launched, paused, activated or deactivated email.
     *
     * @param string $emailId
     * @param array $data
     * @return Response
     */
    public function getEmailResponseSummary($emailId, array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_POST, sprintf('email/%s/responsesummary', $emailId), $data);
    }

    /**
     * Instructs the system to send a test email.
     *
     * @param string $emailId
     * @param array $data
     * @return Response
     */
    public function sendEmailTest($emailId, array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_POST, sprintf('email/%s/sendtestmail', $emailId), $data);
    }

    /**
     * Returns the URL to the online version of an email, provided it has been sent to the specified contact.
     *
     * @param string $emailId
     * @param array $data
     * @return Response
     */
    public function getEmailUrl($emailId, array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_POST, sprintf('email/%s/url', $emailId), $data);
    }

    /**
     * Returns the delivery status of an email.
     *
     * @param array $data
     * @return Response
     */
    public function getEmailDeliveryStatus(array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_POST, 'email/getdeliverystatus', $data);
    }

    /**
     * Lists all the launches of an email with ID, launch date and 'done' status.
     *
     * @param array $data
     * @return Response
     */
    public function getEmailLaunches(array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_GET, 'email/getlaunchesofemail', $data);
    }

    /**
     * Exports the selected fields of all contacts which responded to emails in the specified time range.
     *
     * @param array $data
     * @return Response
     */
    public function getEmailResponses(array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_POST, 'email/getresponses', $data);
    }

    /**
     * Returns a list of email categories which can be used in email creation.
     *
     * @param array $data
     * @return Response
     */
    public function getEmailCategories(array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_GET, 'emailcategory', $data);
    }

    /**
     * Returns a list of external events which can be used in program s .
     *
     * @param array $data
     * @return Response
     */
    public function getEvents(array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_GET, 'event', $data);
    }

    /**
     * Triggers the given event for the specified contact.
     *
     * @param string $eventId
     * @param array $data
     * @return Response
     */
    public function triggerEvent($eventId, array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_POST, sprintf('event/%s/trigger', $eventId), $data);
    }

    /**
     * Fetches the status data of an export.
     *
     * @param array $data
     * @return Response
     */
    public function getExportStatus(array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_GET, 'export', $data);
    }

    /**
     * Returns a list of fields (including custom fields and vouchers) which can be used to personalize content.
     *
     * @return Response
     */
    public function getFields()
    {
        return $this->send(HttpTransportInterface::METHOD_GET, 'field');
    }

    /**
     * Returns the choice options of a field.
     *
     * @param string $fieldId Field ID or custom field name (available in fields mapping)
     * @return Response
     */
    public function getFieldChoices($fieldId)
    {
        return $this->send(HttpTransportInterface::METHOD_GET, sprintf('field/%s/choice', $this->getFieldId($fieldId)));
    }

    /**
     * Returns a customer's files.
     *
     * @param array $data
     * @return Response
     */
    public function getFiles(array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_GET, 'file', $data);
    }

    /**
     * Uploads a file to a media database.
     *
     * @param array $data
     * @return Response
     */
    public function uploadFile(array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_POST, 'file', $data);
    }

    /**
     * Returns a list of segments which can be used as recipient source for the email.
     *
     * @param array $data
     * @return Response
     */
    public function getSegments(array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_GET, 'filter', $data);
    }

    /**
     * Returns a customer's folders.
     *
     * @param array $data
     * @return Response
     */
    public function getFolders(array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_GET, 'folder', $data);
    }

    /**
     * Returns a list of the customer's forms.
     *
     * @param array $data
     * @return Response
     */
    public function getForms(array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_GET, 'form', $data);
    }

    /**
     * Returns a list of languages which you can use in email creation.
     *
     * @return Response
     */
    public function getLanguages()
    {
        return $this->send(HttpTransportInterface::METHOD_GET, 'language');
    }

    /**
     * Returns a list of sources which can be used for creating contacts.
     *
     * @return Response
     */
    public function getSources()
    {
        return $this->send(HttpTransportInterface::METHOD_GET, 'source');
    }

    /**
     * Deletes an existing source.
     *
     * @param string $sourceId
     * @return Response
     */
    public function deleteSource($sourceId)
    {
        return $this->send(HttpTransportInterface::METHOD_DELETE, sprintf('source/%s/delete', $sourceId));
    }

    /**
     * Creates a new source for the customer with the specified name.
     *
     * @param array $data
     * @return Response
     */
    public function createSource(array $data)
    {
        return $this->send(HttpTransportInterface::METHOD_POST, 'source/create', $data);
    }

    /**
     * Set connection/operation timeout in seconds
     *
     * @param int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->transport->setTimeout($timeout);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $body
     * @return Response
     * @throws ServerException
     */
    private function send($method = 'GET', $uri, array $body = [])
    {
        $headers = ['Content-Type: application/json', 'X-WSSE: ' . $this->getAuthenticationSignature()];
        $uri = $this->baseUrl . $uri;

        try {
            $response = $this->transport->send($method, $uri, $headers, $body);
        } catch (\Exception $e) {
            throw new ServerException($e->getMessage());
        }
        $responseJson = $response->getBody();
        $responseArray = json_decode($responseJson, true);

        if (is_null($responseArray)) {
            throw new ServerException(
                sprintf(
                    'Server json response could hot be decoded. Request: %s %s %s %s. Raw response: %s, code: %d',
                    $method,
                    $uri,
                    print_r($headers, true),
                    $body,
                    $responseJson,
                    $response->getCode()
                )
            );
        }

        return new Response($responseArray);
    }

    /**
     * Generate X-WSSE signature used to authenticate
     *
     * @return string
     */
    private function getAuthenticationSignature()
    {
        // Important: Emarsys requires Europe/Vienna timezone
        $created = new \DateTime('now', new \DateTimeZone('Europe/Vienna'));
        // the current time encoded as an ISO 8601 date string
        $iso8601 = $created->format(\DateTime::ISO8601);
        // the md5 of a random string . e.g. a timestamp
        $nonce = md5($created->modify('next friday')->getTimestamp());
        // The algorithm to generate the digest is as follows:
        // Concatenate: Nonce + Created + Secret
        // Hash the result using the SHA1 algorithm
        // Encode the result to base64
        $digest = base64_encode(sha1($nonce . $iso8601 . $this->secret));

        $signature = sprintf(
            'UsernameToken Username="%s", PasswordDigest="%s", Nonce="%s", Created="%s"',
            $this->username,
            $digest,
            $nonce,
            $iso8601
        );

        return $signature;
    }

    /**
     * Convert field names to field ids
     *
     * @param array $data
     * @return array
     */
    private function mapFieldsToIds(array $data)
    {
        $mappedData = [];

        foreach ($data as $name => $value) {
            if (is_numeric($name)) {
                $mappedData[(int)$name] = $value;
            } else {
                $mappedData[$this->getFieldId($name)] = $value;
            }
        }

        return $mappedData;
    }

    /**
     * @param string $filename
     * @return array
     */
    private function parseIniFile($filename)
    {
        $data = parse_ini_file(__DIR__ . '/ini/' . $filename, true);

        return $this->castIniFileValues($data);
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    private function castIniFileValues($data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->castIniFileValues($value);
            } elseif (is_numeric($value)) {
                $data[$key] = (int)$value;
            }
        }

        return $data;
    }
}
