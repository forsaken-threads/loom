<?php

use App\Loom\Inspections;
use App\Loom\QualityControl;
use ForsakenThreads\Loom\Tests\TestCase;
use Illuminate\Validation\Rule;

// TODO: test custom messaging
class QualityControlTest extends TestCase
{
    protected $appendAllTest = [
        'name' => 'max:255|nullable|bail',
        'email' => ['email', 'nullable', 'bail'],
    ];

    protected $appendArrayTest = [
        'name' => ['max:255', 'nullable', 'present', 'required'],
        'email' => ['email', 'nullable', 'present', 'required',],
    ];

    protected $appendMultiStringTest = [
        'name' => 'max:255|nullable|present|bail',
        'email' => ['email', 'nullable', 'present', 'bail'],
    ];

    protected $appendMultiTest = [
        'name' => 'max:255|nullable|present',
        'email' => ['email', 'nullable', 'present'],
    ];

    protected $appendObjectTest = [
        'name' => ['max:255', 'nullable'],
        'email' => ['email', 'nullable'],
    ];

    protected $appendToFieldTest = [
        'name' => 'max:255|nullable|present',
        'email' => ['email', 'nullable'],
    ];

    protected $defaultRules = [
        'name' => 'max:255|nullable',
        'email' => ['email', 'nullable'],
    ];

    protected $pivotTest = [
        'pivot_field' => ['string', 'min:3']
    ];

    protected $removeTest;

    protected $removeMultiTest;

    protected $replaceMultiTest = [
        'name' => 'present',
        'email' => 'present',
    ];

    protected $replaceTest;

    protected $requireAllTest = [
        'name' => 'max:255|nullable|required',
        'email' => ['email', 'nullable', 'required'],
    ];

    protected $requireExceptTest = [
        'name' => 'max:255|nullable|required',
        'email' => ['email', 'nullable', 'required'],
    ];

    protected $requireOnlyTest;

    /**
     * @var QualityControl
     */
    protected $qc;

    public function setUp($withTestResources = true)
    {
        parent::setUp(false);

        $this->defaultRules['role'] = Rule::in(['Admin', 'User', 'Guest']);
        $this->appendAllTest['role'] = [Rule::in(['Admin', 'User', 'Guest']), 'bail'];
        $this->appendArrayTest['role'] = [Rule::in(['Admin', 'User', 'Guest']), 'present', 'required'];
        $this->appendObjectTest['name'][] = Rule::notIn(['bad juju']);
        $this->appendObjectTest['email'][] = Rule::notIn(['bad juju']);
        $this->appendObjectTest['role'] = [Rule::in(['Admin', 'User', 'Guest']), Rule::notIn(['bad juju'])];
        $this->appendToFieldTest['role'] = Rule::in(['Admin', 'User', 'Guest']);
        $this->appendMultiTest['role'] = Rule::in(['Admin', 'User', 'Guest']);
        $this->appendMultiStringTest['role'] = [Rule::in(['Admin', 'User', 'Guest']), 'present', 'bail'];
        $this->removeTest = $this->defaultRules;
        unset($this->removeTest['role']);
        $this->removeMultiTest = $this->defaultRules;
        unset($this->removeMultiTest['email']);
        unset($this->removeMultiTest['role']);
        $this->replaceMultiTest['role'] = Rule::in(['Admin', 'User', 'Guest']);
        $this->replaceTest = $this->defaultRules;
        $this->replaceTest['role'] = Rule::notIn(['bad juju']);
        $this->requireAllTest['role'] = [Rule::in(['Admin', 'User', 'Guest']), 'required'];
        $this->requireExceptTest['role'] = Rule::in(['Admin', 'User', 'Guest']);
        $this->requireOnlyTest = $this->defaultRules;
        $this->requireOnlyTest['role'] = [Rule::in(['Admin', 'User', 'Guest']), 'required'];
        $this->qc = new QualityControl($this->defaultRules);
        $this->qc
            ->withPivot('Resource1', ['pivot_field' => ['string', 'min:3']])
            ->forContext('require-all-test')
                ->requireAll()
            ->forContext('require-except-test')
                ->requireExcept('role')
            ->forContext('require-only-test')
                ->requireOnly('role')
            ->forContext('append-all-test')
                ->appendAll('bail')
            ->forContext('append-array-test')
                ->appendAll(['present', 'required'])
            ->forContext('append-object-test')
                ->appendAll(Rule::notIn(['bad juju']))
            ->forContext('append-to-field-test')
                ->append('name', 'present')
            ->forContext('remove-test')
                ->remove('role')
            ->forContext('remove-multi-test')
                ->remove(['role', 'email'])
            ->forContext('replace-test')
                ->replace('role', Rule::notIn(['bad juju']))
            ->forContext('append-multi-test')
                ->append(['name', 'email'], 'present')
            ->forContext('replace-multi-test')
                ->replace(['name', 'email'], 'present')
            ->forContext('append-multi-string-test')
                ->appendAll('present|bail')
            ->forContext('pivot-test-one')
                ->requireAll('Resource1')
            ->forContext('pivot-test-two')
                ->replace('pivot_field', 'string', 'Resource1');
    }

    public function testDefaultRules()
    {
        $this->assertEquals(new Inspections($this->defaultRules), $this->qc->getRules());
    }

    public function testRequireAll()
    {
        $this->assertEquals(new Inspections($this->requireAllTest), $this->qc->getRules('require-all-test'));
    }

    public function testRequireExcept()
    {
        $this->assertEquals(new Inspections($this->requireExceptTest), $this->qc->getRules('require-except-test'));
    }

    public function testRequireOnly()
    {
        $this->assertEquals(new Inspections($this->requireOnlyTest), $this->qc->getRules('require-only-test'));
    }

    public function testAppendAll()
    {
        $this->assertEquals(new Inspections($this->appendAllTest), $this->qc->getRules('append-all-test'));
    }

    public function testAppendArrayInspection()
    {
        $this->assertEquals(new Inspections($this->appendArrayTest), $this->qc->getRules('append-array-test'));
    }

    public function testAppendObjectInspection()
    {
        $this->assertEquals(new Inspections($this->appendObjectTest), $this->qc->getRules('append-object-test'));
    }

    public function testAppendToField()
    {
        $this->assertEquals(new Inspections($this->appendToFieldTest), $this->qc->getRules('append-to-field-test'));
    }

    public function testReplace()
    {
        $this->assertEquals(new Inspections($this->replaceTest), $this->qc->getRules('replace-test'));
    }

    public function testAppendMulti()
    {
        $this->assertEquals(new Inspections($this->appendMultiTest), $this->qc->getRules('append-multi-test'));
    }

    public function testRemove()
    {
        $this->assertEquals(new Inspections($this->removeTest), $this->qc->getRules('remove-test'));
    }

    public function testRemoveMulti()
    {
        $this->assertEquals(new Inspections($this->removeMultiTest), $this->qc->getRules('remove-multi-test'));
    }

    public function testReplaceMulti()
    {
        $this->assertEquals(new Inspections($this->replaceMultiTest), $this->qc->getRules('replace-multi-test'));
    }

    public function testAppendMultiString()
    {
        $this->assertEquals(new Inspections($this->appendMultiStringTest), $this->qc->getRules('append-multi-string-test'));
    }

    public function testPivot()
    {
        $this->assertEquals(new Inspections($this->pivotTest), $this->qc->getFilterPivot('Resource1'));
    }

    public function testPivotOne()
    {
        $this->assertEquals(new Inspections(['pivot_field' => ['string', 'min:3', 'required']]), $this->qc->getFilterPivot('Resource1', 'pivot-test-one'));
    }

    public function testPivotTwo()
    {
        $this->assertEquals(new Inspections(['pivot_field' => 'string']), $this->qc->getFilterPivot('Resource1', 'pivot-test-two'));
    }
}
