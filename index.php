<?
require_once('crest.php');
// put an example below
echo '<PRE>';
print_r(CRest::call(
    'crm.lead.add',
    [
        'fields' =>[
            'TITLE' => 'Lead tạo bởi app helloworld', // Title*[string]
            'NAME' => 'Chinh', // First Name[string]
            'LAST_NAME' => 'Đặng', // Last Name[string]
        ]
    ])
);
echo '</PRE>';
