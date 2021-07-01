<?php

declare(strict_types=1);

/**
 * Coloca o CKEditor e CKFinder na view
 *
 * @uses viewHelper AbstractHelper
 */

namespace Realejo\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use RuntimeException;

class CKEditor extends AbstractHelper
{
    private static bool $initialized = false;

    /**
     * @var bool|array
     */
    private static $ckFinderConfig = false;

    /**
     * Custom configuration file to override, for example, toolbar buttons.
     *
     * @var bool | string
     */
    private static $ckEditorCustomConfig = false;

    public function init(): void
    {
        if (!self::$initialized) {
            $config = $this->getView()->applicationConfig();

            if (!isset($config['realejo']['vendor']['ckeditor'])) {
                throw new \InvalidArgumentException('CKEditor not defined.');
            }

            $config = $config['realejo']['vendor']['ckeditor'];

            if (empty($config['js'])) {
                throw new \InvalidArgumentException('Javascript not defined for CKEditor.');
            }

            // Adds the ckeditor js e adapters
            foreach ($config['js'] as $file) {
                $this->getView()->headScript()->appendFile($file);
            }

            if (isset($config['ckfinder']) && !empty($config['ckfinder'])) {
                self::$ckFinderConfig = $config['ckfinder'];
            }

            if (isset($config['customConfig']) && !empty($config['customConfig'])) {
                self::$ckEditorCustomConfig = $config['customConfig'];
            }

            self::$initialized = true;
        }
    }

    /**
     * Retorna o javascript do CKEditor
     * @param array|string $fields
     * @param array|string $options
     *
     * @return string js
     */
    public function __invoke($fields = [], $options = []): string
    {
        // Inicializa o CKEditor
        $this->init();

        // Verifica se deve usar o CKFinder
        if (array_key_exists('ckfinder', $options)) {
            // Se não estiver definido retorna erro
            if (self::$ckFinderConfig === false) {
                throw new RuntimeException('CKFinder config not defined');
            }

            // Define os valores do plugin de integração do ckfinder
            foreach (self::$ckFinderConfig as $key => $value) {
                $options[$key] = $value;
            }

            unset($options['ckfinder']);
        }

        // Verifica os inputs que deve colocar o CKEditor
        if (!is_array($fields) && is_string($fields)) {
            $fields = [$fields];
        }

        // Formata as configurações
        $cloneOptions = $options;

        if (array_key_exists('validator', $cloneOptions)) {
            unset($options['validator']);
        }

        if (!isset($options['customConfig']) && self::$ckEditorCustomConfig !== false) {
            $options['customConfig'] = self::$ckEditorCustomConfig;
        }

        $options = (empty($options))
            ? '{}'
            : json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Carrega as opções para cada campo
        $config = '';

        foreach ($fields as $c) {
            if (!array_key_exists('validator', $cloneOptions)) {
                $config .= "$( '$c' ).ckeditor(function() {}, $options);";
            } else {
                $config .= "$( '$c' ).ckeditor(function() {}, $options)
                        .editor.on('change', function() { $('{$cloneOptions['validator']['form']}')
                        .formValidation('revalidateField', '{$cloneOptions['validator']['name']}'); });";
            }
        }

        // Cria a configuração do CKEditor
        return "$(document).ready(function(){ $config });";
    }
}
