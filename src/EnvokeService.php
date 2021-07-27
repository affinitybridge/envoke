<?php

namespace Drupal\envoke;

use GuzzleHttp\Exception\RequestException;

/**
 * Envoke Service.
 *
 */
class EnvokeService {

  /**
   * Constructor.
   */
  public function __construct($config) {
    $this->config = $config;
    $this->httpClient = \Drupal::httpClient();
  }

  /**
   * Return boolean to indicate sending email is successful.
   *
   * @param array $data array of data fields required by Envoke API
   * @param string $to receiver email address
   * @return bool
   */
  public function sendEmail($to, $message)
  {
    $username = $this->config->get('envoke_api_id');
    $password = $this->config->get('envoke_api_key');
    $envoke_send_mail_api_url = 'https://e1.envoke.com/api/v4legacy/send/SendEmails';

    if (empty($username) || empty($password)) {
      return FALSE;
    }

    $data = [
      "SendEmails" => [
        [
          "EmailDataArray" => [
            [
              "email" => [$message]
            ]
          ]
        ]
      ]
    ];

    if ($this->insertContactIfNotExist($to) == TRUE) {
      try {
        $send_mail_request_response = $this->httpClient->post($envoke_send_mail_api_url, [
          'auth' => [$username, $password],
          'json' => $data,
        ]);
        $result = json_decode($send_mail_request_response->getBody(), true);
        return $result[0]['result_value'] == "true";
      } catch (RequestException $e) {
        return FALSE;
      }
    } else {
      return FALSE;
    }
  }

  /**
   * Return boolean to indicate the insertion of a new contact with the given email is successful
   * @param string $email
   * @param array $subscribed array of subscriptions (e.g Newsletter, ...)
   * @param array $unsubscribed array of subscriptions (e.g Newsletter, ...)
   * @param bool $forSubscription to update subscriptions
   * @return bool
   */
  public function insertContactIfNotExist($email, $subscribed = [], $unsubscribed = [], $forSubscription = false) {
    $username = $this->config->get('envoke_api_id');
    $password = $this->config->get('envoke_api_key');

    if ($forSubscription == true) {
      $username = $this->config->get('envoke_subscription_api_id');
      $password = $this->config->get('envoke_subscription_api_key');
    }

    $envoke_contact_api_url = 'https://e1.envoke.com/v1/contacts';

    $contact = [
      "email" => $email,
      "consent_status" => "Express",
      "consent_description" => "Express consent given on website homepage form."
    ];

    $get_contact_response = $this->httpClient->get($envoke_contact_api_url, [
        'auth' => [$username, $password],
        'query' => ['filter[email]' => $email],
        'http_errors' => false
    ]);

    if ($get_contact_response->getStatusCode() == 200) {
      $contacts = json_decode($get_contact_response->getBody());
      if (is_array($contacts) && count($contacts) > 0) {
        if ($forSubscription == true) {
          $new_contact_data = [];
          $existing_contact = $contacts[0];
          if (!empty($subscribed) || !empty($unsubscribed)) {
            // update subscriptions
            $current_subscriptions = $existing_contact->interests;
            foreach ($current_subscriptions as $key) {
              $new_contact_data["interests"][$key] = "Set";
            }
            foreach ($subscribed as $key) {
              $new_contact_data["interests"][$key] = "Set";
            }
            foreach ($unsubscribed as $key) {
              $new_contact_data["interests"][$key] = "Unset";
            }

            // switch to Express to allow resubscribe
            if (count($subscribed) > 0) {
              $new_contact_data['consent_status'] = 'Express';
            }
            // update user
            $existing_contact_id = $existing_contact->id;
            $update_contact_response = $this->httpClient->patch($envoke_contact_api_url . "/{$existing_contact_id}", [
              'auth' => [$username, $password],
              'json' => $new_contact_data
            ]);
            $result = json_decode($update_contact_response->getBody(), true);
            return $result['success'];
          }
        } else {
          return TRUE;
        }
      }
    }

    try {
      if ($forSubscription) {
        foreach ($subscribed as $key) {
          $contact["interests"][$key] = "Set";
        }
        foreach ($unsubscribed as $key) {
          $contact["interests"][$key] = "Unset";
        }
      }
      $insert_contact_response = $this->httpClient->post($envoke_contact_api_url, [
        'auth' => [$username, $password],
        'json' => $contact,
      ]);
      $result = json_decode($insert_contact_response->getBody(), true);
      return $result['success'];
    } catch (RequestException $e) {
      return FALSE;
    }
  }

  public function getContactSubscriptions($email) {
    $username = $this->config->get('envoke_subscription_api_id');
    $password = $this->config->get('envoke_subscription_api_key');

    $envoke_contact_api_url = 'https://e1.envoke.com/v1/contacts';

    $get_contact_response = $this->httpClient->get($envoke_contact_api_url, [
      'auth' => [$username, $password],
      'query' => ['filter[email]' => $email],
      'http_errors' => false
    ]);

    $contact_subscriptions = [];

    if ($get_contact_response->getStatusCode() == 200) {
      $contacts = json_decode($get_contact_response->getBody());
      if (is_array($contacts) && count($contacts) > 0) {
        $existing_contact = $contacts[0];
        if ($existing_contact->consent_status !== 'Revoked') {
          $contact_subscriptions = $existing_contact->interests;
        }
      }
    }

    return $contact_subscriptions;
  }
}