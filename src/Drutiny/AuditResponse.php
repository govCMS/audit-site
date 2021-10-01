<?php

namespace Drutiny\AuditResponse;

use Drutiny\Policy;
use Drutiny\Audit;

/**
 * Class AuditResponse.
 *
 * @package Drutiny\AuditResponse
 */
class AuditResponse {

    protected $policy;

    protected $state = Audit::NOT_APPLICABLE;

    protected $remediated = FALSE;

    protected $tokens = [];

    /**
     * AuditResponse constructor.
     *
     * @param Policy $policy
     *   A policy object of type Drutiny\Policy.
     */
    public function __construct(Policy $policy) {
        $this->policy = $policy;
    }

    /**
     * Get the AudiResponse Policy.
     */
    public function getPolicy()
    {
        return $this->policy;
    }

    /**
     * Set the state of the response.
     */
    public function set($state = NULL, array $tokens) {
        switch (TRUE) {
            case ($state === Audit::SUCCESS):
            case ($state === Audit::PASS):
                $state = Audit::SUCCESS;
                break;

            case ($state === Audit::FAILURE):
            case ($state === Audit::FAIL):
                $state = Audit::FAIL;
                break;

            case ($state === Audit::NOT_APPLICABLE):
            case ($state === NULL):
                $state = Audit::NOT_APPLICABLE;
                break;

            case ($state === Audit::IRRELEVANT):
            case ($state === Audit::WARNING):
            case ($state === Audit::WARNING_FAIL):
            case ($state === Audit::NOTICE):
            case ($state === Audit::ERROR):
                // Do nothing. These are all ok.
                break;

            default:
                throw new AuditResponseException("Unknown state set in Audit Response: $state");
        }

        $this->state = $state;
        $this->tokens = $tokens;
    }

    /**
     * Get the name.
     *
     * @return string
     *   The policy name.
     */
    public function getName() {
        return $this->policy->get('name', $this->tokens);
    }

    /**
     * Get the title.
     *
     * @return string
     *   The checks title.
     */
    public function getTitle() {
        return $this->policy->get('title', $this->tokens);
    }

    /**
     * Get the description for the check performed.
     *
     * @return string
     *   Translated description.
     */
    public function getDescription() {
        return $this->policy->get('description', $this->tokens);
    }

    /**
     * Get the exception message if present.
     */
    public function getExceptionMessage()
    {
        return isset($this->tokens['exception']) ? $this->tokens['exception'] : '';
    }

    /**
     * Get the type of response based on policy type and audit response.
     */
    public function getType()
    {
        if ($this->isNotApplicable()) {
            return 'not-applicable';
        }
        if ($this->hasError()) {
            return 'error';
        }
        if ($this->isNotice()) {
            return 'notice';
        }
        if ($this->hasWarning()) {
            return 'warning';
        }
        $policy_type = $this->policy->get('type');
        if ($policy_type == 'data') {
            return 'notice';
        }
        return $this->isSuccessful() ? 'success' : 'failure';
    }

    /**
     * Get the remediation for the check performed.
     *
     * @return string
     *   Translated description.
     */
    public function getRemediation() {
        return $this->policy->has('remediation') ? $this->policy->get('remediation', $this->tokens) : '';
    }

    /**
     * Get the failure message for the check performed.
     *
     * @return string
     *   Translated description.
     */
    public function getFailure() {
        return $this->policy->has('failure') ? $this->policy->get('failure', $this->tokens) : '';
    }

    /**
     * Get the success message for the check performed.
     *
     * @return string
     *   Translated description.
     */
    public function getSuccess() {
        return $this->policy->get('success', $this->tokens);
    }

    /**
     * Get the warning message for the check performed.
     *
     * @return string
     *   Translated description.
     */
    public function getWarning() {
        return $this->hasWarning() ? $this->policy->get('warning', $this->tokens) : '';
    }

    /**
     *
     */
    public function isSuccessful() {
        return $this->state === Audit::SUCCESS || $this->remediated || $this->isNotice() || $this->state === Audit::WARNING;
    }

    /**
     *
     */
    public function isNotice() {
        return $this->state === Audit::NOTICE;
    }

    /**
     *
     */
    public function hasWarning() {
        return $this->state === Audit::WARNING || $this->state === Audit::WARNING_FAIL;
    }

    public function isRemediated($set = NULL)
    {
        if (isset($set)) {
            $this->remediated = $set;
        }
        return $this->remediated;
    }

    public function hasError()
    {
        return $this->state === Audit::ERROR;
    }

    public function isNotApplicable()
    {
        return $this->state === Audit::NOT_APPLICABLE;
    }

    public function isIrrelevant()
    {
        return $this->state === Audit::IRRELEVANT;
    }

    public function getSeverity()
    {
        return $this->policy->getSeverityName();
    }

    public function getSeverityCode()
    {
        return $this->policy->getSeverity();
    }

    /**
     * Get the response based on the state outcome.
     *
     * @return string
     *   Translated description.
     */
    public function getSummary() {
        $summary = [];
        switch (TRUE) {
            case ($this->state === Audit::NOT_APPLICABLE):
                $summary[] = "This policy is not applicable to this site.";
                break;

            case ($this->state === Audit::ERROR):
                $summary[] = 'Could not determine the state of ' . $this->getTitle() . ' due to a server/ssh error.';
                break;

            case ($this->state === Audit::WARNING):
                $summary[] = $this->getWarning();
            case ($this->state === Audit::SUCCESS):
            case ($this->state === Audit::PASS):
            case ($this->state === Audit::NOTICE):
                $summary[] = $this->getSuccess();
                break;

            case ($this->state === Audit::WARNING_FAIL):
                $summary[] = $this->getWarning();
            case ($this->state === Audit::FAILURE):
            case ($this->state === Audit::FAIL):
                $summary[] = $this->getFailure();
                break;

            default:
                throw new AuditResponseException("Unknown AuditResponse state ({$this->state}). Cannot generate summary for '" . $this->getTitle() . "'.");
                break;
        }
        return implode(PHP_EOL, $summary);
    }
}
