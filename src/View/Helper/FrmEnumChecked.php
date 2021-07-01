<?php

declare(strict_types=1);

/**
 * Retorna o HTML de um <select> para usar em formulários
 *
 * @param string $nome Name/ID a ser usado no <select>
 * @param string $selecionado Valor pré selecionado
 * @param string $opts Opções adicionais
 *
 * Os valores de option serão os valores dos campos definidos em $status
 *
 * As opções adicionais podem ser
 *  - placeholder => legenda quando nenhum estiver selecionado e/ou junto com show-empty
 *                   se usado com FALSE, nunca irá mostrar o vazio, mesmo que não tenha um selecionado
 *  - show-empty  => mostra um <option> vazio no inicio mesmo com um selecionado
 *
 * @return string
 */

namespace Realejo\View\Helper;

use Realejo\Enum\Enum;
use Realejo\Enum\EnumFlagged;
use Laminas\View\Helper\AbstractHelper;

class FrmEnumChecked extends AbstractHelper
{
    public function __invoke(Enum $enum, array $options = []): string
    {
        // Recupera os registros
        $names = $enum::getNames();

        // Remove the names that cannot be user
        if (isset($options['not-in'])) {
            foreach ($options['not-in'] as $v) {
                unset($names[$v]);
            }
        }

        $showDescription = (isset($options['show-description']) && $options['show-description'] === true);

        // Monta as opções
        $values = [];
        if (!empty($names)) {
            foreach ($names as $v => $n) {
                $checked = ($enum instanceof EnumFlagged) ? $enum->has($v) : $enum->is($v);
                $checked = ($checked) ? '<i class="fa fa-check-square-o"></i>' : '<i class="fa fa-square-o"></i>';

                if ($showDescription) {
                    $n .= ' <span class="tip" title="'
                        . $enum->getValueDescription($v)
                        . '"><i class="fa fa fa-question-circle"></i></span>';
                }

                $values[] = "<p class=\"form-control-static\"> $checked $n </p>";
            }
        }

        if (isset($options['cols'])) {
            $countValues = count($values);
            $slice = ceil($countValues / $options['cols']);
            $columns = [];
            $columnSize = round(12 / $options['cols']);
            for ($c = 1; $c <= $options['cols']; $c++) {
                $columns[$c] = "<div class=\"col-xs-$columnSize\">"
                    . implode('', array_slice($values, ($c - 1) * $slice, $slice))
                    . '</div>';
            }
            return '<div class="row">' . implode('', $columns) . '</div>';
        }

        return implode('', $values);
    }
}
