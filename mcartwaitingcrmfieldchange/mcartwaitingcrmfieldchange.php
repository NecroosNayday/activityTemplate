<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Bizproc\FieldType;
use Bitrix\Main\Localization\Loc;

class CBPMcartWaitingCrmFieldChange extends CBPCompositeActivity implements
    IBPEventActivity,
    IBPActivityExternalEventListener
{
    const ACTIVITY = 'McartWaitingCrmFieldChange';
    protected $taskId = 0;
    protected $taskStatus = false;
    protected $isInEventActivityMode = false;

    public function __construct($name)
    {
        parent::__construct($name);

        $this->arProperties = [
            'isChange' => 'N',
            'elementId' => '',
            'eventModule' => '',
            'eventName' => '',
            'fieldCode' => '',
        ];

        $this->setPropertiesTypes([
            'eventModule' => [
                'Type' => FieldType::STRING,
            ],
            'eventName' => [
                'Type' => FieldType::STRING,
            ],
            'elementId' => [
                'Type' => FieldType::STRING,
            ],
            'fieldCode' => [
                'Type' => FieldType::STRING,
            ],
            'isChange' => [
                'Type' => FieldType::BOOL,
            ],
        ]);
    }

    public function execute()
    {
        $this->subscribe($this);
        $this->isInEventActivityMode = false;

        return CBPActivityExecutionStatus::Executing;
    }

    public static function getPropertiesDialog(
        $documentType,
        $activityName,
        $arWorkflowTemplate,
        $arWorkflowParameters,
        $arWorkflowVariables,
        $arCurrentValues = null,
        $formName = '',
        $popupWindow = null,
        $siteId = ''
    )
    {
        if (!\Bitrix\Main\Loader::includeModule('crm')) {
            return '';
        }

        $dialog = new PropertiesDialog(__FILE__, [
            'documentType' => $documentType,
            'activityName' => $activityName,
            'workflowTemplate' => $arWorkflowTemplate,
            'workflowParameters' => $arWorkflowParameters,
            'workflowVariables' => $arWorkflowVariables,
            'currentValues' => $arCurrentValues,
            'formName' => $formName,
            'siteId' => $siteId,
        ]);
        $dialog->setMap(static::getPropertiesMap($documentType));

        return $dialog;
    }

    protected static function getPropertiesMap(array $documentType, array $context = []): array
    {
        return [
            'elementId' => [
                'Name' => Loc::getMessage('MCART_WCFC_ELEMENT_ID'),
                'FieldName' => 'element_id',
                'Type' => 'string',
            ],
            'eventModule' => [
                'Name' => Loc::getMessage('MCART_WCFC_EVENT_MODULE'),
                'FieldName' => 'event_module',
                'Type' => 'string',
            ],
            'eventName' => [
                'Name' => Loc::getMessage('MCART_WCFC_EVENT_NAME'),
                'FieldName' => 'event_name',
                'Type' => 'string',
            ],
            'fieldCode' => [
                'Name' => Loc::getMessage('MCART_WCFC_FIELD_CODE'),
                'FieldName' => 'field_code',
                'Type' => 'string',
            ]

        ];
    }

    public static function getPropertiesDialogValues(
        $documentType,
        $activityName,
        &$arWorkflowTemplate,
        &$arWorkflowParameters,
        &$arWorkflowVariables,
        $arCurrentValues,
        &$errors
    )
    {
        $properties = [
            'elementId' => $arCurrentValues['element_id'],
            'eventModule' => $arCurrentValues['event_module'],
            'eventName' => $arCurrentValues['event_name'],
            'fieldCode' => $arCurrentValues['field_code'],
        ];

        $errors = self::validateProperties(
            $properties,
            new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser)
        );

        if ($errors) {
            return false;
        }

        $currentActivity = &CBPWorkflowTemplateLoader::findActivityByName($arWorkflowTemplate, $activityName);
        $currentActivity['Properties'] = $properties;

        return true;
    }

    public function handleFault(Exception $exception)
    {
        $status = $this->cancel();
        if ($status == CBPActivityExecutionStatus::Canceling) {
            return CBPActivityExecutionStatus::Faulting;
        }

        return $status;
    }

    public function cancel()
    {
        if (!$this->isInEventActivityMode && $this->taskId > 0) {
            $this->unsubscribe($this);
        }

        return CBPActivityExecutionStatus::Closed;
    }

    public function subscribe(IBPActivityExternalEventListener $eventHandler)
    {
        if ($eventHandler == null) {
            throw new Exception('eventHandler');
        }

        $this->isInEventActivityMode = true;

        $schedulerService = $this->workflow->getService('SchedulerService');
        $schedulerService->subscribeOnEvent(
            $this->workflow->getInstanceId(),
            $this->name,
            $this->eventModule,
            $this->eventName,
        );

        $this->workflow->addEventHandler($this->name, $eventHandler);
    }

    public function unsubscribe(IBPActivityExternalEventListener $eventHandler)
    {
        if ($eventHandler == null) {
            throw new Exception('eventHandler');
        }

        $schedulerService = $this->workflow->getService('SchedulerService');
        $schedulerService->unSubscribeOnEvent(
            $this->workflow->getInstanceId(),
            $this->name,
            $this->eventModule,
            $this->eventName,
        );

        $this->workflow->removeEventHandler($this->name, $eventHandler);
    }

    public function onExternalEvent($arEventParameters = [])
    {
        if ($this->executionStatus != CBPActivityExecutionStatus::Closed) {
            if (
                (int)$this->elementId !== (int)$arEventParameters[0]['ID']
                || !isset($arEventParameters[0][$this->fieldCode])
                || empty($arEventParameters[0][$this->fieldCode])
            ) {
                return;
            }
            $this->arProperties['IsChange'] = 'Y';

            $this->unsubscribe($this);
            $this->workflow->closeActivity($this);
        }
    }
}
