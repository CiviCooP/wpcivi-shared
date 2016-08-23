CiviCRM Entity classes: example usage
=====================================

TODO: Netjes en definitief mooi overzicht maken

```php
$wpcivi = \WPCivi\Shared\Civi\WPCiviApi::getInstance();

// --------------------

echo "\nGET 1 CONTACT\n\n";

$contact = new \WPCivi\Jourcoop\Entity\Contact;
$contact->load(1);
echo $contact->id . ": " . $contact->getValue('display_name') . "\n";

// --------------------

echo "\nGET 1 CONTACT BY EMAIL\n\n";

$contact = new \WPCivi\Jourcoop\Entity\Contact;
$contact->loadBy(['email' => 'dev@example.com']);
echo $contact->id . ": " . $contact->getValue('display_name') . "\n";

// --------------------

echo "\nGET CURRENT WORDPRESS USER'S CONTACT\n\n";

$contact = new \WPCivi\Jourcoop\Entity\Contact;
$contact->loadCurrentWPUser();
echo $contact->id . ": " . $contact->getValue('display_name') . "\n";

// --------------------

echo "\nGET WORDPRESS USER FOR CONTACT\n\n";

$contact = new \WPCivi\Jourcoop\Entity\Contact;
$contact->loadBy(['email' => 'dev@example.com']);
echo "Contact id " . $contact->id . " (" . $contact->display_name . "):\n";
$wpuser = $contact->getWPUser();
echo "WP_User " . $wpuser->ID . " - " . $wpuser->display_name . "\n\n";

// --------------------

echo "\nGET ALL CONTACTS\n\n";

/* $contacts = \WPCivi\Jourcoop\Entity\Contact::getContacts();
foreach($contacts as $contact) {
    echo $contact->id . ": " . $contact->getValue('display_name') . "\n";
} */
echo "[COMMENTED OUT]\n\n";

// --------------------

echo "\nGET ALL MEMBERS (met een default en een custom field; gebruik gerust getContacts om te testen)\n\n";

// Gebruik vooral getContacts() voor nu, members nieuwe stijl zijn er immers nog weinig
$contacts = \WPCivi\Jourcoop\Entity\Contact::getMembers();
foreach($contacts as $contact) {
    echo $contact->id . ": " . $contact->getValue('display_name') . " - " . $contact->getCustom('Bank_Account_IBAN') . " - " . $contact->getCustom('KvK_No') . "\n";
}

// --------------------

echo "\nGET CONTACT FIELD LIST\n\n";

$contact = new \WPCivi\Jourcoop\Entity\Contact;
$contact->load(316);

/*
 * print_r($contact->getFields());
 */
echo "[COMMENTED OUT]\n\n";

// --------------------

echo "\nGET ALL CONTACT VALUES\n\n";

print_r($contact->getArray());

// --------------------

echo "\nGET ALL CONTACT MEMBERSHIPS\n\n";

$memberships = $contact->getActiveMemberships();
foreach($memberships as $m) {
    print_r($m->getArray());
}

// --------------------

echo "\nSET NEW CONTACT DATA\n\n";

// Load contact
$contact = new WPCivi\Jourcoop\Entity\Contact;
$contact->loadBy(['id' => 316]);

// Set new data
$contact->setValue('first_name', 'Testerdetesterdetest');
$contact->setCustom('KvK_No', '53581709');

// Save and show result
echo "Calling save() for contact 316: ";
$result = $contact->save();
var_dump($result);

// --------------------

echo "\nGET CASES / OPDRACHTEN\n\n";

$cases = \WPCivi\Jourcoop\Entity\Cases::getJobs();
foreach($cases as $case) {
    /** @var $case \WPCivi\Jourcoop\Entity\Cases */
    echo $case->id . ": " . $case->getValue('subject') . " - " . $case->getCaseStatusName() . " - " . $case->getValue('start_date') . "\n";
}

// --------------------

echo "\nCASE INFORMATION\n\n";

$case = new \WPCivi\Jourcoop\Entity\Cases;
$case->load(6);

echo "ID: " . $case->id . "\n";
echo "STATUS: " . $case->getCaseStatusName() . "\n";
echo "SERVICE/dienst-type: " . $case->getCaseServiceName() . "\n";
// print_r($case->getArray());

foreach($case->getContacts() as $contact) {
    echo "Contact: " . $contact->contact_id . " - " . $contact->display_name . " - " . $contact->role . "\n";
}
foreach($case->getActivities() as $activity) {
    echo "Activity: " . $activity->id . " - " . ($activity->subject ? $activity->subject : "No subject") . " - " . $activity->activity_date_time . "\n";
}

// --------------------

echo "\nGET CASES FOR CURRENT USER (waarin we een - maakt niet uit welke - rol hebben)\n\n";

$cases = \WPCivi\Jourcoop\Entity\Cases::getCasesForCurrentUser();
foreach($cases as $case) {
    /** @var $case \WPCivi\Jourcoop\Entity\Cases */
    echo $case->id . ": " . $case->getValue('subject') . " - " . $case->getCaseStatusName() . " - " . $case->getValue('start_date') . "\n";
}

// --------------------

echo "\nGET CASE FIELD LIST\n\n";

$case = new \WPCivi\Jourcoop\Entity\Cases;
$case->load(6);

// print_r($case->getFields());
echo "[COMMENTED OUT]\n\n";

// --------------------

echo "\nSET NEW CASE DATA (werkt niet ivm core error, nog te bekijken)\n\n";

$case->setValue('status_id', 'Submitted');
$case->setCustom('Price', '800');

// Save and show result
echo "Calling save() for case 6: ";
$result = $case->save();
var_dump($result);

// --------------------
```