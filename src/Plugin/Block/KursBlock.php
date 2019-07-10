<?php
namespace Drupal\kurs_nbp\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
/**
 * Provides a 'Kurs' Block
 *
 * @Block(
 *   id = "kurs_block",
 *   admin_label = @Translation("Kurs block"),
 * )
 */
class KursBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    if (!empty($config['kurs_block_settings'])) {
      $currencies = $config['kurs_block_settings'];
    }
    else {
      $currencies = $this->t('USD EUR');
    }

    $values=[];
    $markup = "<div>";
    $date = "";

    $currencies = explode(' ', $currencies);
    foreach($currencies as $currency) {
      $url = 'http://api.nbp.pl/api/exchangerates/rates/c/' . strval($currency) . '?format=json';
      $json = file_get_contents($url);
      $jsonarray = json_decode($json, true);
      $values[$currency] = [
        "buy" => $jsonarray["rates"][0]["ask"],
        "sell" => $jsonarray["rates"][0]["bid"],
      ];
      $date = $jsonarray["rates"][0]["effectiveDate"];
    }

    $time = '<time datetime="' . $date . ' 8:00">' . $date . ", godz. 8:00</time>";
    $markup = $markup . $time;

    foreach ($values as $currencyname => $value) {
      $markup = $markup . "<div>" . $currencyname . ": skup: " . $values[$currencyname]["buy"] . ", sprzedaz: " . $values[$currencyname]["sell"] . "</div>";
    }

    return array (
      '#markup' => $markup."</div>"
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $default_config = \Drupal::config('kurs_nbp.settings');
    $config = $this->getConfiguration();
    $form['kurs_block_settings'] = array (
      '#type' => 'textfield',
      '#title' => $this->t('Currencies'),
      '#description' => $this->t('Type space separated currencies. Default: "USD EUR"'),
      '#default_value' => isset($config['kurs_block_settings']) ? $config['kurs_block_settings'] : $default_config->get('kurs.currencies'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('kurs_block_settings', $form_state->getValue('kurs_block_settings'));
  }
}
