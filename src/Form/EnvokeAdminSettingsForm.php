<?php

namespace Drupal\envoke\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements an Mandrill Admin Settings form.
 */
class EnvokeAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'envoke_admin_settings';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('envoke.settings');

    $formats = filter_formats();
    $options = array('' => $this->t('-- Select --'));
    foreach ($formats as $v => $format) {
      $options[$v] = $format->get('name');
    }
    $form['email_options']['envoke_filter_format'] = array(
      '#type' => 'select',
      '#title' => $this->t('Input format'),
      '#description' => $this->t('If selected, the input format to apply to the message body before sending to the Envoke API.'),
      '#options' => $options,
      '#default_value' => array($config->get('envoke_filter_format')),
    );
    $form['envoke_api_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Envoke API ID'),
      '#description' => $this->t('Envoke API ID'),
      "#default_value" => $config->get('envoke_api_id'),
    ];
    $form['envoke_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Envoke API KEY'),
      '#description' => $this->t('Envoke API KEY'),
      "#default_value" => $config->get('envoke_api_key'),
    ];
    // subaccount for newsletter
    $form['envoke_subscription_api_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Envoke API ID for Subscription'),
      '#description' => $this->t('Envoke API ID'),
      "#default_value" => $config->get('envoke_subscription_api_id'),
    ];
    $form['envoke_subscription_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Envoke API KEY for Subscription'),
      '#description' => $this->t('Envoke API KEY'),
      "#default_value" => $config->get('envoke_subscription_api_key'),
    ];
    $form['envoke_campaign'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Campaign name'),
      '#description' => $this->t('Campaign name. Default will use the site name'),
      "#default_value" => $config->get('envoke_campaign'),
    ];
    $form['envoke_email_from'] = [
      '#type' => 'textfield',
      '#title' => $this->t('From email'),
      '#description' => $this->t('The sender email address. Default will use default account from email'),
      "#default_value" => $config->get('envoke_email_from'),
    ];
    $form['envoke_name_from'] = [
      '#type' => 'textfield',
      '#title' => $this->t('From name'),
      '#description' => $this->t('The sender name. Default will use default account from name'),
      "#default_value" => $config->get('envoke_name_from'),
    ];
    $form['envoke_email_reply'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reply to email'),
      '#description' => $this->t('The email address to reply. Default will use default account from email'),
      "#default_value" => $config->get('envoke_email_reply'),
    ];

    return parent::buildForm($form, $form_state);;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('envoke.settings')
    ->set('envoke_api_id', $form_state->getValue('envoke_api_id'))
    ->set('envoke_api_key', $form_state->getValue('envoke_api_key'))
    ->set('envoke_subscription_api_id', $form_state->getValue('envoke_subscription_api_id'))
    ->set('envoke_subscription_api_key', $form_state->getValue('envoke_subscription_api_key'))
    ->set('envoke_email_from', $form_state->getValue('envoke_email_from'))
    ->set('envoke_name_from', $form_state->getValue('envoke_name_from'))
    ->set('envoke_email_reply', $form_state->getValue('envoke_email_reply'))
    ->set('envoke_campaign', $form_state->getValue('envoke_campaign'))
    ->set('envoke_filter_format', $form_state->getValue('envoke_filter_format'))
    ->save();
  }

  /**
   * {@inheritdoc}.
   */
  protected function getEditableConfigNames() {
    return ['envoke.settings'];
  }
}
