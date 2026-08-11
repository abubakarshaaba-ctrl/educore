<?php

namespace Tests\Unit;

use App\Models\LessonPlan;
use App\Models\Subject;
use App\Services\Curriculum\WebLessonResearchService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebLessonResearchServiceTest extends TestCase
{
    public function test_it_retrieves_caches_and_labels_allowlisted_open_education_evidence(): void
    {
        Cache::flush();
        config(['lesson_research.providers'=>[config('lesson_research.providers.0')],'lesson_research.max_results'=>2]);
        Http::fake(['en.wikipedia.org/*'=>Http::response(['query'=>['pages'=>[
            7=>['index'=>1,'title'=>'Biology','fullurl'=>'https://en.wikipedia.org/wiki/Biology',
                'extract'=>str_repeat('Biology is the scientific study of life, living organisms, their structures, functions, development and relationships. ',4)],
        ]]],200)]);

        $plan=new LessonPlan(['topic'=>'Introduction to Biology','subtopic'=>'Branches of Biology']);
        $plan->setRelation('subject',new Subject(['name'=>'Biology']));
        $first=app(WebLessonResearchService::class)->forLessonPlan($plan);
        $second=app(WebLessonResearchService::class)->forLessonPlan($plan);

        $this->assertCount(1,$first);
        $this->assertSame('web_reference',$first[0]['source_type']);
        $this->assertSame('supplementary_only',$first[0]['usage']);
        $this->assertStringStartsWith('web:',$first[0]['evidence_id']);
        $this->assertSame($first,$second);
        Http::assertSentCount(1);
    }

    public function test_khan_academy_is_explicitly_blocked_from_automated_research(): void
    {
        $this->assertContains('www.khanacademy.org',config('lesson_research.blocked_hosts'));
        $this->assertContains('khanacademy.org',config('lesson_research.blocked_hosts'));
    }
}
