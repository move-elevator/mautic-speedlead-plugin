<?php
declare(strict_types = 1);

namespace MauticPlugin\SpeedleadBundle\Service;

use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\LeadBundle\DataObject\LeadManipulator;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Entity\LeadNote;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Entity\ListLeadRepository;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\NoteModel;
use \Mautic\StageBundle\Entity\StageRepository;
use MauticPlugin\SpeedleadBundle\Statics\ReportConsent;
use Symfony\Component\Translation\TranslatorInterface;

class ReportContactMapperService
{
    /**
     * @var ModelFactory
     */
    private $modelFactory;

    /**
     * @var IpLookupHelper
     */
    private $ipLookupHelper;

    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * @var StageRepository
     */
    private $stageRepository;

    /**
     * @var CompanyRepository
     */
    private $companyRepository;

    /**
     * @var FairApiService
     */
    private $fairApiService;

    /**
     * @var UrlGeneratorService
     */
    private $urlGeneratorService;

    /**
     * @var LeadListRepository
     */
    private $leadListRepository;

    /**
     * @var ListLeadRepository
     */
    private $listLeadRepository;

    /**
     * @var LeadEventLogRepository
     */
    private $eventLogRepository;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        ModelFactory $modelFactory,
        IpLookupHelper $ipLookupHelper,
        LeadRepository $leadRepository,
        StageRepository $stageRepository,
        LeadListRepository $leadListRepository,
        ListLeadRepository $listLeadRepository,
        CompanyRepository $companyRepository,
        FairApiService $fairApiService,
        UrlGeneratorService $urlGeneratorService,
        LeadEventLogRepository $eventLogRepository,
        TranslatorInterface $translator
    ) {
        $this->modelFactory = $modelFactory;
        $this->ipLookupHelper = $ipLookupHelper;
        $this->leadRepository = $leadRepository;
        $this->stageRepository = $stageRepository;
        $this->leadListRepository = $leadListRepository;
        $this->listLeadRepository = $listLeadRepository;
        $this->companyRepository = $companyRepository;
        $this->fairApiService = $fairApiService;
        $this->urlGeneratorService = $urlGeneratorService;
        $this->eventLogRepository = $eventLogRepository;
        $this->translator = $translator;
    }

    public function createContact(array $report, array $featureSettings): void
    {
        if (true === empty($report['contact']['email'])) {
            return;
        }

        // only import reports with consents not declined
        if (ReportConsent::CONSENT_STATUS_DECLINED === $report['consent']['status']){
            return;
        }

        /** @var LeadModel $leadModel */
        $leadModel = $this->modelFactory->getModel('lead');

        /** @var CompanyModel $companyModel */
        $companyModel = $this->modelFactory->getModel('lead.company');

        $initialStage = $featureSettings['initialStage'];
        $segmentIds = $featureSettings['segments'];

        // create and save the lead and company
        $lead = $this->createLead($report, $initialStage, $leadModel);

        if (true === $lead instanceof Lead) {
            $this->handleLeadEventLog($lead, $report);

            $leadModel->saveEntity($lead);

            if (true === $lead->isNewlyCreated()) {
                /** @var NoteModel $noteModel */
                $noteModel = $this->modelFactory->getModel('lead.note');

                $note = $this->createNote($lead, $report);

                $noteModel->saveEntity($note);

                // set segments when chosen
                foreach ($segmentIds as $segmentId) {
                    $segment = $this->leadListRepository->find($segmentId);

                    $listLead = new ListLead();
                    $listLead->setList($segment);
                    $listLead->setLead($lead);
                    $listLead->setDateAdded(new \DateTime());

                    $log = new LeadEventLog();
                    $log
                        ->setLead($lead)
                        ->setBundle('lead')
                        ->setAction('added')
                        ->setObject('segment')
                        ->setObjectId($segment->getId())
                        ->setProperties(
                            [
                                'object_description' => $segment->getName(),
                            ]
                        );

                    $this->eventLogRepository->saveEntity($log);
                    $this->listLeadRepository->saveEntity($listLead);
                }

                $this->eventLogRepository->clear();
            }

            $company = $this->companyRepository->findOneBy(['name' => $lead->getCompany()]);

            if (true === $company instanceof Company) {
                // update and save the company that was just created
                $company = $this->updateCompany(
                    $this->companyRepository->findOneBy(['name' => $lead->getCompany()]),
                    $report,
                    $companyModel
                );
                $companyModel->saveEntity($company);
            }
        }

    }

    private function createLead(array $report, int $initialStage, LeadModel $leadModel): Lead
    {
        $lead = new Lead();
        $lead->setNewlyCreated(true);

        // IP address of the request
        $ipAddress = $this->ipLookupHelper->getIpAddress();

        // set initial stage when it is not 0 (no stage)
        if (0 !== $initialStage) {
            $lead->setStage($this->stageRepository->find($initialStage));
        }

        // Updated/new fields
        $leadFields = [
            'firstname' => $report['contact']['forename'],
            'lastname' => $report['contact']['surname'],
            'title' => sprintf('%s %s', $report['contact']['salutation'], $report['contact']['title']),
            'email' => $report['contact']['email'],
            'company' => $report['company']['name'],
            'position' => $report['company']['position'],
            'phone' => $report['contact']['phone'],
            'mobile' => $report['contact']['mobile'],
            'fax' => $report['contact']['fax'],
            'address1' => $report['company']['street'],
            'city' => $report['company']['city'],
            'zipcode' => $report['company']['zipCode'],
            'country' => $report['company']['country'],
            'website' => $report['company']['website']
        ];

        // Optionally check for identifier fields to determine if the lead is unique
        $uniqueLeadFields    = $this->modelFactory->getModel('lead.field')->getUniqueIdentifierFields();
        $uniqueLeadFieldData = [];

        // Check if unique identifier fields are included
        $inList = array_intersect_key($leadFields, $uniqueLeadFields);
        foreach ($inList as $k => $v) {
            if (true === empty($inList[$k])) {
                unset($inList[$k]);
            }

            if (true === array_key_exists($k, $uniqueLeadFields)) {
                if (true === is_string($v)) {
                    $uniqueLeadFieldData[$k] = strtolower(trim($v));

                    continue;
                }

                $uniqueLeadFieldData[$k] = $v;
            }
        }

        // If there are unique identifier fields, check for existing leads based on lead data
        if (count($inList) && count($uniqueLeadFieldData)) {
            $existingLeads = $this->leadRepository->getLeadsByUniqueFields($uniqueLeadFieldData);

            if (false === empty($existingLeads)) {
                // Existing found so merge the two leads
                $leadFields = $this->handleExistingLeadMerge($leadFields, $existingLeads[0]);
                $lead = $existingLeads[0];
            }

            // Get the lead's currently associated IPs
            $leadIpAddresses = $lead->getIpAddresses();

            // If the IP is not already associated, do so (the addIpAddress will automatically handle ignoring
            // the IP if it is set to be ignored in the Configuration)
            if (false === $leadIpAddresses->contains($ipAddress)) {
                $lead->addIpAddress($ipAddress);
            }
        }

        // Set the lead's data
        $leadModel->setFieldValues($lead, $leadFields);

        return $lead;
    }

    private function handleLeadEventLog(Lead $lead, array $report): void
    {
        $fair = $this->getFair();

        // set the manipulator so that mautic's LeadModel can take care of ceating an eventLog-entry
        // when it saves the lead to the db
        $lead->setManipulator(new LeadManipulator(
            'lead',
            'lead',
            null,
            $this->translator->trans(
                'mautic.speedlead.event_log_msg', [
                    '%fair%' => $fair['eventName'],
                    '%url%' => $this->urlGeneratorService->generateUrlReportFrontend(
                        $this->fairApiService->getInstance(),
                        $report['id'],
                        $fair['id']
                    )
                ]
            )
        ));
    }

    private function createNote(Lead $lead, array $report): LeadNote
    {
        $fair = $this->getFair();

        $note = new LeadNote();

        $note->setType('general');
        $note->setText(
            $this->translator->trans('mautic.speedlead.note.contact_created', [
                '%fair%' => $fair['eventName'],
                '%date%' => (new \DateTime($report['created']))->format('d.m.Y H:i:s'),
                '%url%' => $this->urlGeneratorService->generateUrlReportFrontend(
                    $this->fairApiService->getInstance(),
                    $report['id'],
                    $fair['id']
                 )
            ])
        );
        $note->setLead($lead);
        $note->setDateTime(new \DateTime());

        return $note;
    }

    private function updateCompany(Company $company, array $report, CompanyModel $companyModel): Company
    {
        // Updated/new fields
        $companyFields = [
            'companyname' => $report['company']['name'],
            'companywebsite' => $report['company']['website'],
            'companyaddress1' => $report['company']['street'],
            'companyzipcode' => $report['company']['zipCode'],
            'companycity' => $report['company']['city'],
            'companycountry' => $report['company']['country']
        ];

        //handle merge with existing data
        $companyFields = $this->handleExistingCompanyMerge($companyFields, $company);

        // Set the lead's data
        $companyModel->setFieldValues($company, $companyFields);

        return $company;
    }

    private function handleExistingLeadMerge(array $leadFields, Lead $existingLead): array
    {
        $fieldsToUpdate = [];

        foreach ($leadFields as $fieldKey => $fieldValue) {
            if (true === empty($existingLead->getField($fieldKey)['value'])) {
                $fieldsToUpdate[$fieldKey] = $fieldValue;
            }
        }
        return $fieldsToUpdate;
    }

    private function handleExistingCompanyMerge(array $companyFields, Company $existingCompany): array
    {
        $fieldsToUpdate = [];

        foreach ($companyFields as $fieldKey => $fieldValue) {
            if ($fieldKey === 'companyname' && true === empty($existingCompany->getName())) {
                $fieldsToUpdate[$fieldKey] = $fieldValue;
            }
            if ($fieldKey === 'companywebsite' && true === empty($existingCompany->getWebsite())) {
                $fieldsToUpdate[$fieldKey] = $fieldValue;
            }
            if ($fieldKey === 'companyaddress1' && true === empty($existingCompany->getAddress1())) {
                $fieldsToUpdate[$fieldKey] = $fieldValue;
            }
            if ($fieldKey === 'companyzipcode' && true === empty($existingCompany->getZipcode())) {
                $fieldsToUpdate[$fieldKey] = $fieldValue;
            }
            if ($fieldKey === 'companycity' && true === empty($existingCompany->getCity())) {
                $fieldsToUpdate[$fieldKey] = $fieldValue;
            }
            if ($fieldKey === 'companycountry' && true === empty($existingCompany->getCountry())) {
                $fieldsToUpdate[$fieldKey] = $fieldValue;
            }
        }

        return $fieldsToUpdate;
    }

    private function getFair(): ?array
    {
        $fair = $this->fairApiService->getFair();

        if (null !== $fair) {
            return $fair;
        }

        $this->fairApiService->callApiShowFair();

        return $this->fairApiService->getFair();
    }
}
