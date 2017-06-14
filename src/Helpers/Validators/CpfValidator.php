<?php

namespace Jaacoder\Yii2Activated\Helpers\Validators;

use yii\validators\Validator;

/**
 * Class CpfValidator.
 */
class CpfValidator extends Validator
{

    public function validateAttribute($model, $attribute)
    {
        $validator = \Respect\Validation\Validator::cpf();
        
        try {
            $validator->check($model->$attribute);
            //
        } catch (\Respect\Validation\Exceptions\ValidationException $e) {
            $this->addError($model, $attribute, '"{attribute}" não é um número de cpf válido');
        }
    }

}
