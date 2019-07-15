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

    if (!empty($config['kurs_block_currencies'])) {
      $currencies = $config['kurs_block_currencies'];
    }
    else {
      $currencies = $this->t('USD EUR');
    }

    if (!empty($config['kurs_block_style'])) {
      $style = $config['kurs_block_style'];;
    }
    else {
      $style = $this->t('dark');
    }

    $values=[];
    $markup = '<div class="KursBlock"><h3>Kursy walut</h3>';
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

    $time = '<time class="KursBlock-disabled" datetime="' . $date . ' 8:00">' . $date . ", godz. 8:00</time>";
    $markup = $markup . $time;

    foreach ($values as $currencyname => $value) {
      $markup = $markup . '<div><div class="KursBlock-currency">' . $currencyname . '</div><div> <div class="KursBlock-disabled">Skup: </div><div>' . $values[$currencyname]["buy"] . '</div></div><div><div class="KursBlock-disabled">Sprzedaż: </div><div>' . $values[$currencyname]["sell"] . "</div></div></div>";
    }

    if ( $style == "light" ) {
      $style_css = "kurs_nbp/light";
    } else {
      $style_css = "kurs_nbp/dark";
    }

    return array (
      '#markup' => $markup."</div>",
      '#attached' => [
        'library' => [
          'kurs_nbp/kurs_nbp',
          $style_css,
        ]
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $default_config = \Drupal::config('kurs_nbp.settings');
    $config = $this->getConfiguration();
    $form['kurs_block_currencies'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Currencies'),
      '#description' => $this->t('Type space separated currencies. Default: "USD EUR"'),
      '#default_value' => isset($config['kurs_block_currencies']) ? $config['kurs_block_currencies'] : $default_config->get('kurs.currencies'),
    ];
    $form['kurs_block_style'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Style'),
      '#description' => $this->t('Choose between "light" or "dark". Default: "dark"'),
      '#default_value' => isset($config['kurs_block_style']) ? $config['kurs_block_style'] : $default_config->get('kurs.style'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['kurs_block_currencies'] = $values['kurs_block_currencies'];
    $this->configuration['kurs_block_style'] = $values['kurs_block_style'];
  }
}
