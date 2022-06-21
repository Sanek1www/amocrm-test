<?php

namespace App;

use \AmoCRM\Client\AmoCRMApiClient;
use \AmoCRM\Collections\CustomFieldsValuesCollection;
use \AmoCRM\Models\CustomFields\MultiselectCustomFieldModel;
use \AmoCRM\Collections\CustomFields\CustomFieldEnumsCollection;
use \AmoCRM\Models\CustomFieldsValues\ValueModels\MultiselectCustomFieldValueModel;
use \AmoCRM\Filters\PagesFilter;
use \AmoCRM\Models\CustomFieldsValues\{
    MultiselectCustomFieldValuesModel,
    ValueCollections\MultiselectCustomFieldValueCollection
};
use \AmoCRM\Models\{
    CustomFields\EnumModel,
    LeadModel,
    ContactModel,
    CompanyModel,
};
use \AmoCRM\Collections\{
    ContactsCollection,
    Leads\LeadsCollection,
};

class AmoCRMService
{

    private AmoCRMApiClient $apiClient;

    private const CHUNK_SIZE = 50;

    public function __construct(AmoCRMApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function createThreeRelatedEntities(int $num): LeadsCollection
    {
        $leadsService = $this->apiClient->leads();

        $leadsCollection = new LeadsCollection();
        for ($i = 0; $i < $num; $i++) {
            $leadsCollection
                ->add(
                    (new LeadModel())->setName('Lead #' . $i)->setCompany(
                        (new CompanyModel())->setName('Company #' . $i)
                    )->setContacts(
                        (new ContactsCollection())->add(
                            (new ContactModel())->setName('Contact #' . $i)
                        )
                    )
                );
        }
        
       
        $result = new LeadsCollection();
        foreach ($leadsCollection->chunk(self::CHUNK_SIZE) as $collection) {
            $result = $result::make(array_merge($leadsService->addComplex($collection)->all(), $result->all()));
        }
       
        return $result;
    }

    public function addMultiSelectFieldToLeads(): MultiselectCustomFieldModel
    {
        $customFieldsService = $this->apiClient->customFields('leads');
        $multiSelect = (new MultiselectCustomFieldModel())->setEnums(
            (new CustomFieldEnumsCollection())
                ->make([
                    (new EnumModel())->setValue('test-value1')->setSort(1),
                    (new EnumModel())->setValue('test-value2')->setSort(2),
                    (new EnumModel())->setValue('test-value3')->setSort(3),
                ])
            )
            ->setName('Тестовый мультисписок');


        return $customFieldsService->addOne($multiSelect);
    }

    public function updateMultiselectValueInLeads(MultiselectCustomFieldModel $multiSelectModel): void
    {
        $leadsService = $this->apiClient->leads();

        $page = 1;
        $pageFilter = (new PagesFilter())->setLimit(self::CHUNK_SIZE)->setPage($page);
        $leadsCollection = new LeadsCollection();
        while (true) {
            try {
                $leadsCollection = $leadsCollection->make(array_merge($leadsCollection->all(), $leadsService->get($pageFilter)->all()));
            } catch (\AmoCRM\Exceptions\AmoCRMApiNoContentException $e) {
                break;
            }
            $pageFilter->setPage(++$page);
        }
        

        $fieldId = $multiSelectModel->getId();
        foreach ($leadsCollection as $lead) {
            $lead->setCustomFieldsValues(
                (new CustomFieldsValuesCollection())->add(
                    (new MultiselectCustomFieldValuesModel())->setFieldId($fieldId)->setValues(
                        (new MultiselectCustomFieldValueCollection())
                            ->add((new MultiselectCustomFieldValueModel)->setValue('test-value1'))
                            ->add((new MultiselectCustomFieldValueModel)->setValue('test-value3'))
                    )
                )
            );
        }
        
        foreach ($leadsCollection->chunk(self::CHUNK_SIZE) as $collection) {
            $leadsService->update($collection);
        }
        
    }
}