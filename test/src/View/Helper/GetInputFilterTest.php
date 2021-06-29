<?php

declare(strict_types=1);

namespace RealejoTest\View\Helper;

use PHPUnit\Framework\TestCase;
use Realejo\View\Helper\GetInputFilter;
use Laminas\Form\Element\Text;
use Laminas\Form\Form;
use Laminas\I18n\Translator\Translator;
use Laminas\I18n\Validator\IsInt;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\Between;
use Laminas\Validator\NotEmpty;

class GetInputFilterTest extends TestCase
{
    public function testConstruct(): void
    {
        $helper = new GetInputFilter();
        self::assertInstanceOf(GetInputFilter::class, $helper);
        self::assertEquals('pt_BR', $helper->getTranslator()->getLocale());
    }

    public function testConstructEnglish(): void
    {
        $translator = new Translator();
        $helper = new GetInputFilter($translator);
        self::assertEquals('en_US', $helper->getTranslator()->getLocale());
    }

    public function testGetFormValidationFieldsArray(): void
    {
        $form = new Form();

        // Adiciona um campo texto
        $form->add(
            [
                'name' => 'campo1',
                'type' => Text::class
            ]
        );

        // Cria um input filter
        $inputFilter = new InputFilter();

        // Adiciona validação [obrigatório, inteiro, não vazio, entre 1 e 3]
        $inputFilter->add(
            [
                'name' => 'campo1',
                'required' => true,
                'filters' => [],
                'validators' => [
                    ['name' => IsInt::class],
                    ['name' => NotEmpty::class],
                    [
                        'name' => Between::class,
                        'options' => [
                            'min' => 1,
                            'max' => 3
                        ]
                    ],
                ],
            ]
        );

        $form->setInputFilter($inputFilter);

        $helper = new GetInputFilter();

        $fields = $helper->getFormValidationFieldsArray($form);
        self::assertIsArray($fields);
        self::assertNotEmpty($fields);
        self::assertCount(1, $fields);
        self::assertArrayHasKey('campo1', $fields);
        self::assertArrayHasKey('validators', $fields['campo1']);
        self::assertCount(3, $fields['campo1']['validators']);
        self::assertArraySubset(['integer', 'notEmpty', 'between'], array_keys($fields['campo1']['validators']));

        self::assertArrayHasKey('message', $fields['campo1']['validators']['integer']);
        self::assertArrayHasKey('message', $fields['campo1']['validators']['notEmpty']);

        self::assertArrayHasKey('message', $fields['campo1']['validators']['between']);
        self::assertArrayHasKey('min', $fields['campo1']['validators']['between']);
        self::assertArrayHasKey('max', $fields['campo1']['validators']['between']);
    }

    public function testGetFormValidationFieldsJson(): void
    {
        // Cria um form
        $form = new Form();

        // Adiciona um campo texto
        $form->add(
            [
                'name' => 'campo1',
                'type' => Text::class
            ]
        );

        // Cria um input filter
        $inputFilter = new InputFilter();

        // Adiciona validação [obrigatório, inteiro, não vazio, entre 1 e 3]
        $inputFilter->add(
            [
                'name' => 'campo1',
                'required' => true,
                'filters' => [],
                'validators' => [
                    ['name' => IsInt::class],
                    ['name' => NotEmpty::class],
                    [
                        'name' => Between::class,
                        'options' => [
                            'min' => 1,
                            'max' => 3
                        ]
                    ],
                ],
            ]
        );

        $form->setInputFilter($inputFilter);

        $helper = new GetInputFilter();
        $json = $helper->getFormValidationFieldsJSON($form);
        self::assertIsString($json);

        $fields = json_decode($json, true);
        self::assertIsArray($fields);

        self::assertNotEmpty($fields);
        self::assertCount(1, $fields);
        self::assertArrayHasKey('campo1', $fields);
        self::assertArrayHasKey('validators', $fields['campo1']);
        self::assertCount(3, $fields['campo1']['validators']);
        self::assertArraySubset(['integer', 'notEmpty', 'between'], array_keys($fields['campo1']['validators']));

        self::assertArrayHasKey('message', $fields['campo1']['validators']['integer']);
        self::assertArrayHasKey('message', $fields['campo1']['validators']['notEmpty']);

        self::assertArrayHasKey('message', $fields['campo1']['validators']['between']);
        self::assertArrayHasKey('min', $fields['campo1']['validators']['between']);
        self::assertArrayHasKey('max', $fields['campo1']['validators']['between']);
    }
}
