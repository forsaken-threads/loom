<?php

use App\Webstuhl\QualityControl;
use ForsakenThreads\Webstuhl\Tests\TestCase;
use Illuminate\Validation\Rule;

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

    public function setUp()
    {
        parent::setUp();

        $this->defaultRules['role'] = Rule::in(['Admin', 'User', 'Guest']);
        $this->appendAllTest['role'] = [Rule::in(['Admin', 'User', 'Guest']), 'bail'];
        $this->appendArrayTest['role'] = [Rule::in(['Admin', 'User', 'Guest']), 'present', 'required'];
        $this->appendObjectTest['name'][] = Rule::notIn(['bad juju']);
        $this->appendObjectTest['email'][] = Rule::notIn(['bad juju']);
        $this->appendObjectTest['role'] = [Rule::in(['Admin', 'User', 'Guest']), Rule::notIn(['bad juju'])];
        $this->appendToFieldTest['role'] = Rule::in(['Admin', 'User', 'Guest']);
        $this->appendMultiTest['role'] = Rule::in(['Admin', 'User', 'Guest']);
        $this->appendMultiStringTest['role'] = [Rule::in(['Admin', 'User', 'Guest']), 'present', 'bail'];
        $this->replaceMultiTest['role'] = Rule::in(['Admin', 'User', 'Guest']);
        $this->replaceTest = $this->defaultRules;
        $this->replaceTest['role'] = Rule::notIn(['bad juju']);
        $this->requireAllTest['role'] = [Rule::in(['Admin', 'User', 'Guest']), 'required'];
        $this->requireExceptTest['role'] = Rule::in(['Admin', 'User', 'Guest']);
        $this->requireOnlyTest = $this->defaultRules;
        $this->requireOnlyTest['role'] = [Rule::in(['Admin', 'User', 'Guest']), 'required'];
        $this->qc = new QualityControl($this->defaultRules);
        $this->qc
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
            ->forContext('replace-test')
                ->replace('role', Rule::notIn(['bad juju']))
            ->forContext('append-multi-test')
                ->append(['name', 'email'], 'present')
            ->forContext('replace-multi-test')
                ->replace(['name', 'email'], 'present')
            ->forContext('append-multi-string-test')
                ->appendAll('present|bail');
    }

    public function testDefaultRules()
    {
        $this->assertEquals($this->defaultRules, $this->qc->getRules());
    }

    public function testRequireAll()
    {
        $this->assertEquals($this->requireAllTest, $this->qc->getRules('require-all-test'));
    }

    public function testRequireExcept()
    {
        $this->assertEquals($this->requireExceptTest, $this->qc->getRules('require-except-test'));
    }

    public function testRequireOnly()
    {
        $this->assertEquals($this->requireOnlyTest, $this->qc->getRules('require-only-test'));
    }

    public function testAppendAll()
    {
        $this->assertEquals($this->appendAllTest, $this->qc->getRules('append-all-test'));
    }

    public function testAppendArrayInspection()
    {
        $this->assertEquals($this->appendArrayTest, $this->qc->getRules('append-array-test'));
    }

    public function testAppendObjectInspection()
    {
        $this->assertEquals($this->appendObjectTest, $this->qc->getRules('append-object-test'));
    }

    public function testAppendToField()
    {
        $this->assertEquals($this->appendToFieldTest, $this->qc->getRules('append-to-field-test'));
    }

    public function testReplace()
    {
        $this->assertEquals($this->replaceTest, $this->qc->getRules('replace-test'));
    }

    public function testAppendMulti()
    {
        $this->assertEquals($this->appendMultiTest, $this->qc->getRules('append-multi-test'));
    }

    public function testReplaceMulti()
    {
        $this->assertEquals($this->replaceMultiTest, $this->qc->getRules('replace-multi-test'));
    }

    public function testAppendMultiString()
    {
        $this->assertEquals($this->appendMultiStringTest, $this->qc->getRules('append-multi-string-test'));
    }
}
