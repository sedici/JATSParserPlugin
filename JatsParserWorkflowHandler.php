<?php

namespace APP\plugins\generic\jatsParser;

use APP\core\Application;
use APP\facades\Repo;
use APP\handler\Handler;
use APP\publication\Publication;
use APP\submission\Submission;
use PKP\core\JSONMessage;
use PKP\core\PKPApplication;
use PKP\facades\Locale;
use PKP\security\authorization\SubmissionAccessPolicy;
use PKP\security\Role;
use PKP\submissionFile\SubmissionFile;

class JatsParserWorkflowHandler extends Handler
{
    /** @var JatsParserPlugin */
    public $_plugin;

    public function __construct($plugin = null)
    {
        parent::__construct();
        $this->_plugin = $plugin;
        $this->addRoleAssignment(
            [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN, Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_ASSISTANT],
            ['formConfig']
        );
    }

    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new SubmissionAccessPolicy(
            $request,
            $args,
            $roleAssignments,
            'submissionId'
        ));
        return parent::authorize($request, $args, $roleAssignments);
    }

    public function formConfig($args, $request)
    {
        $submissionId = (int) $request->getUserVar('submissionId');
        $submission = $submissionId ? Repo::submission()->get($submissionId) : null;
        if (!$submission) {
            return new JSONMessage(false, __('common.error'));
        }

        $publicationId = (int) $request->getUserVar('publicationId');
        $publication = $publicationId ? Repo::publication()->get($publicationId) : $submission->getCurrentPublication();

        if (!$publication || $publication->getData('submissionId') != $submission->getId()) {
            return new JSONMessage(false, __('common.error'));
        }

        $context = $request->getContext();

        // Get production ready XML submission files
        $submissionFiles = Repo::submissionFile()
            ->getCollector()
            ->filterBySubmissionIds([$submission->getId()])
            ->filterByFileStages([SubmissionFile::SUBMISSION_FILE_PRODUCTION_READY])
            ->getMany()
            ->filter(function ($file) {
                $mimetype = $file->getData('mimetype');
                return $mimetype === 'text/xml' || $mimetype === 'application/xml';
            });

        $publicationApiUrl = $request->getDispatcher()->url(
            $request,
            PKPApplication::ROUTE_API,
            $context->getPath(),
            'submissions/' . $submission->getId() . '/publications/' . $publication->getId()
        );

        $supportedLocaleNames = $context->getSupportedSubmissionLocaleNames();
        $locales = [];
        foreach ($supportedLocaleNames as $localeKey => $localeName) {
            $locales[] = [
                'key' => $localeKey,
                'label' => $localeName,
            ];
        }

        $prevErrorReporting = error_reporting(error_reporting() & ~E_DEPRECATED);
        require_once __DIR__ . '/classes/components/forms/PublicationJATSUploadForm.php';
        $form = new PublicationJATSUploadForm(
            $publicationApiUrl,
            $locales,
            $publication,
            $submissionFiles,
            __('plugins.generic.jatsParser.publication.jats.description')
        );

        $formConfig = $form->getConfig();
        error_reporting($prevErrorReporting);

        return new JSONMessage(true, $formConfig);
    }
}

if (!class_exists('JatsParserWorkflowHandler', false)) {
    class_alias(\APP\plugins\generic\jatsParser\JatsParserWorkflowHandler::class, 'JatsParserWorkflowHandler');
}
