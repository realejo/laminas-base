<?php

namespace RealejoTest\View\Helper;

/**
 * Version test case.
 */

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
        $this->assertInstanceOf(GetInputFilter::class, $helper);
        $this->assertInstanceOf(Translator::class, $helper->getTranslator());
        $this->assertEquals('pt_BR', $helper->getTranslator()->getLocale());
    }

    public function testConstructEnglish(): void
    {
        $translator = new Translator();
        $helper = new GetInputFilter($translator);
        $this->assertEquals('en_US', $helper->getTranslator()->getLocale());
    }

    public function testGetFormValidationFieldsArray(): void
    {
        $form = new Form();

        // Adiciona um campo texto
        $form->add([
            'name' => 'campo1',
            'type' => Text::class
        ]);

        // Cria um input filter
        $inputFilter = new InputFilter();

        // Adiciona validação [obrigatório, inteiro, não vazio, entre 1 e 3]
        $inputFilter->add([
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
        ]);

        $form->setInputFilter($inputFilter);

        $helper = new GetInputFilter();

        $fields = $helper->getFormValidationFieldsArray($form);
        $this->assertTrue(is_array($fields));
        $this->assertNotEmpty($fields);
        $this->assertCount(1, $fields);
        $this->assertArrayHasKey('campo1', $fields);
        $this->assertArrayHasKey('validators', $fields['campo1']);
        $this->assertCount(3, $fields['campo1']['validators']);
        $this->assertArraySubset(['integer', 'notEmpty', 'between'], array_keys($fields['campo1']['validators']));

        $this->assertArrayHasKey('message', $fields['campo1']['validators']['integer']);
        $this->assertArrayHasKey('message', $fields['campo1']['validators']['notEmpty']);

        $this->assertArrayHasKey('message', $fields['campo1']['validators']['between']);
        $this->assertArrayHasKey('min', $fields['campo1']['validators']['between']);
        $this->assertArrayHasKey('max', $fields['campo1']['validators']['between']);
    }

    public function testGetFormValidationFieldsJson()
    {
        // Cria um form
        $form = new Form();

        // Adiciona um campo texto
        $form->add([
            'name' => 'campo1',
            'type' => Text::class
        ]);

        // Cria um input filter
        $inputFilter = new InputFilter();

        // Adiciona validação [obrigatório, inteiro, não vazio, entre 1 e 3]
        $inputFilter->add([
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
        ]);

        $form->setInputFilter($inputFilter);

        $helper = new GetInputFilter();
        $json = $helper->getFormValidationFieldsJSON($form);
        $this->assertTrue(is_string($json));

        $fields = json_decode($json, true);
        $this->assertTrue(is_array($fields));

        $this->assertNotEmpty($fields);
        $this->assertCount(1, $fields);
        $this->assertArrayHasKey('campo1', $fields);
        $this->assertArrayHasKey('validators', $fields['campo1']);
        $this->assertCount(3, $fields['campo1']['validators']);
        $this->assertArraySubset(['integer', 'notEmpty', 'between'], array_keys($fields['campo1']['validators']));

        $this->assertArrayHasKey('message', $fields['campo1']['validators']['integer']);
        $this->assertArrayHasKey('message', $fields['campo1']['validators']['notEmpty']);

        $this->assertArrayHasKey('message', $fields['campo1']['validators']['between']);
        $this->assertArrayHasKey('min', $fields['campo1']['validators']['between']);
        $this->assertArrayHasKey('max', $fields['campo1']['validators']['between']);
    }
}
