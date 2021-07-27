<?php

namespace Drupal\envoke\Plugin\Mail;

use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Mail\MailFormatHelper;

/**
 * Modify the Drupal mail system to use Envoke when sending emails.
 *
 * @Mail(
 *   id = "envoke_mail",
 *   label = @Translation("Envoke mailer"),
 *   description = @Translation("Sends the message through Envoke.")
 * )
 */
class EnvokeMailer implements MailInterface {

  /**
   * The Config Factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->config = \Drupal::service('config.factory')->get('envoke.settings');
    $this->httpClient = \Drupal::httpClient();
    $this->envokeService = \Drupal::service('envoke.envoke_service');
  }

  /**
   * Concatenate and wrap the email body for either plain-text or HTML emails.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return array
   *   The formatted $message.
   */
  public function format(array $message) {
    // Join the body array into one string.
    if (is_array($message['body'])) {
      $message['body'] = implode("\n\n", $message['body']);
    }
    return $message;
  }

  /**
   * Send the email message.
   *
   * @see drupal_mail()
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return bool
   *   TRUE if the mail was successfully accepted, otherwise FALSE.
   */
  public function mail(array $message) {
    global $base_url;

    // Render the body in an HTML email template.
    $format = $this->config->get('envoke_filter_format');
    if (!empty($format)) {
      $message['body'] = check_markup($message['body'], $format);
    }

    // If the field contains no html tags we can assume newlines will need be
    // converted to <br>.
    if (strlen(strip_tags($message['body'])) === strlen($message['body'])) {
      $message['body'] = str_replace("\r", '', $message['body']);
      $message['body'] = str_replace("\n", '<br>', $message['body']);
    }

    $build = [
      '#theme' => 'envoke_mail',
      '#body'  => $message['body'],
      '#base_url' => $base_url,
    ];
    $templated_email = \Drupal::service('renderer')->render($build);

    $envoke_message = [
      "to_email" => $message['to'],
      "message_subject" => $message['subject'],
      "message_html" => $templated_email . ' {@pref-99}',
      "message_text" => MailFormatHelper::htmlToText($message['body']) . ' {@pref-99}',
    ];

    if (!empty($this->config->get('envoke_campaign'))) {
      $envoke_message["campaign_name"] = $this->config->get('envoke_campaign');
    } else {
      $envoke_message["campaign_name"] = \Drupal::config('system.site')->get('name');
    }

    if (isset($message["from"])) {
      $envoke_message['from_email'] = $message["from"];
    }
    if (!empty($this->config->get('envoke_email_from'))) {
      $envoke_message['from_email'] = $this->config->get('envoke_email_from');
    }
    if (!empty($this->config->get('envoke_name_from'))) {
      $envoke_message['from_name'] = $this->config->get('envoke_name_from');
    }
    if (isset($message["reply-to"])) {
      $envoke_message['reply_email'] = $message["reply-to"];
    }
    if (!empty($this->config->get('envoke_email_reply'))) {
      $envoke_message['reply_email'] = $this->config->get('envoke_email_reply');
    }

    return $this->envokeService->sendEmail($message['to'], $envoke_message);
  }

}
