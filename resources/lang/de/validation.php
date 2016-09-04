<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted'             => ':attribute muss akzeptiert werden.',
    'active_url'           => ':attribute ist keine g&uuml;ltige URL.',
    'after'                => ':attribute muss ein Datum nach dem :date sein.',
    'alpha'                => ':attribute darf nur Buchstaben enthalten.',
    'alpha_dash'           => ':attribute darf nur Buchstaben, Zahlen und Bindestriche enthalten.',
    'alpha_num'            => ':attribute darf nur Buchstaben und Zahlen enthalten.',
    'array'                => ':attribute muss ein Array sein.',
    'before'               => ':attribute muss ein Datum vor dem :date sein.',
    'between'              => [
        'numeric' => ':attribute muss zwischen :min und :max liegen.',
        'file'    => ':attribute darf mindestens :min und h&ouml;chstens :max Kilobyte gro&szlig; sein.',
        'string'  => ':attribute darf mindestens :min und h&ouml;chstens :max Zeichen enthalten.',
        'array'   => ':attribute muss mindestens :min und h&ouml;chstens :max Elemente enthalten.',
    ],
    'boolean'              => ':attribute muss entweder &quot;true&quot; oder &quot;false&quot; sein.',
    'confirmed'            => ':attribute und dessen Best&auml;tigung stimmen nicht &uuml;berein.',
    'date'                 => ':attribute ist kein g&uuml;ltiges Datum.',
    'date_format'          => ':attribute hat das nicht das Datumsformat :format.',
    'different'            => ':attribute und :other d&uuml;rfen nicht &uuml;bereinstimmen.',
    'digits'               => ':attribute muss exakt :digits Zahlen enthalten.',
    'digits_between'       => ':attribute muss zwischen :min und :max Zahlen enthalten.',
    'distinct'             => ':attribute hat einen doppelten Wert.',
    'email'                => ':attribute ist keine g&uuml;ltige E-Mail-Adresse.',
    'exists'               => 'Das ausgew&auml;hlte :attribute ist nicht g&uuml;ltig.',
    'filled'               => ':attribute muss angegeben werden.',
    'image'                => ':attribute ist kein Bild.',
    'in'                   => 'Das ausgew&auml;lte :attribute ist ung&uuml;ltig.',
    'in_array'             => ':attribute ist nicht in :other vorhanden.',
    'integer'              => ':attribute muss eine ganze Zahl sein.',
    'ip'                   => ':attribute muss eine g&uuml;ltige IP-Adresse enthalten.',
    'json'                 => ':attribute muss ein g&uuml;ltiger JSON-String sein.',
    'max'                  => [
        'numeric' => ':attribute darf nicht gr&ouml;&szlig;er als :max sein.',
        'file'    => ':attribute darf nicht gr&ouml;&szlig;er als :max Kilobytes sein.',
        'string'  => ':attribute darf nicht mehr als :max Zeichen enthalten.',
        'array'   => ':attribute darf nicht mehr als :max Elemente enthalten.',
    ],
    'mimes'                => ':attribute muss den folgenden Dateityp besitzen: :values.',
    'min'                  => [
        'numeric' => ':attribute muss mindestens :min sein.',
        'file'    => ':attribute muss mindestens :min Kilobytes gro&szlig; sein.',
        'string'  => ':attribute muss mindestens :min Zeichen enthalten.',
        'array'   => ':attribute muss mindestens :min Elemente enthalten.',
    ],
    'not_in'               => 'Das ausgew&auml;hlte :attribute ist ung&uuml;ltig.',
    'numeric'              => ':attribute muss eine Zahl sein.',
    'present'              => ':attribute muss vorhanden sein.',
    'regex'                => ':attribute ist nicht korrekt formatiert.',
    'required'             => ':attribute muss angegeben werden.',
    'required_if'          => ':attribute muss angegeben werden, wenn :other den Wert :value hat.',
    'required_unless'      => ':attribute muss angegeben werden, solange :other einen der folgenden Werte hat: :values.',
    'required_with'        => ':attribute muss angegeben werden, wenn :values angegeben wurden.',
    'required_with_all'    => ':attribute muss angegeben werden, wenn alle folgenden Werte angegeben wurden: :values.',
    'required_without'     => ':attribute muss angegeben werden, wenn :values nicht angegeben wurde.',
    'required_without_all' => ':attribute muss angegeben werden, wenn keiner der folgenden Werte angegeben wurden: :values.',
    'same'                 => ':attribute und :other m&uuml;ssen dieselben Werte besitzen.',
    'size'                 => [
        'numeric' => ':attribute muss :size Gr&ouml;&szlig;e haben.',
        'file'    => ':attribute muss :size Kilobytes gro&szlig; sein.',
        'string'  => ':attribute muss genau :size Zeichen enthalten.',
        'array'   => ':attribute muss genau :size Elemente enthalten.',
    ],
    'string'               => ':attribute muss eine Zeichenkette sein.',
    'timezone'             => ':attribute muss eine g&uuml;ltige Zeitzone sein.',
    'unique'               => ':attribute wurde bereits anderweitig angegeben.',
    'url'                  => ':attribute hat ein falsches Format.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [],

];
