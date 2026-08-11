<?php

namespace App\Services\Curriculum;

use App\Models\LessonPlan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebLessonResearchService
{
    public function forLessonPlan(LessonPlan $plan): array
    {
        if (! config('lesson_research.enabled', true)) return [];

        $query=trim(implode(' ',array_filter([
            $plan->subject?->name,
            $plan->topic,
            collect(preg_split('/[,;\n]+/',(string)$plan->subtopic))->filter()->take(3)->implode(' '),
        ])));
        if($query==='') return [];

        $key='lesson-web-research:'.hash('sha256',mb_strtolower($query));
        return Cache::remember($key,now()->addHours(config('lesson_research.cache_hours',168)),fn()=>$this->research($query));
    }

    private function research(string $query): array
    {
        $results=[];
        foreach(config('lesson_research.providers',[]) as $provider){
            if(count($results)>=config('lesson_research.max_results',4)) break;
            try{
                $response=Http::acceptJson()->withUserAgent('EduCore-Curriculum-Research/1.0 (+https://educoreng.online)')
                    ->timeout(config('lesson_research.timeout_seconds',10))->retry(1,200)
                    ->get($provider['api'],[
                        'action'=>'query','generator'=>'search','gsrsearch'=>$query,'gsrnamespace'=>0,
                        'gsrlimit'=>2,'prop'=>'extracts|info','exintro'=>1,'explaintext'=>1,'inprop'=>'url','format'=>'json','origin'=>'*',
                    ]);
                if(!$response->successful()) continue;
                foreach(collect($response->json('query.pages',[]))->sortBy('index') as $page){
                    $title=trim((string)($page['title']??'')); $extract=$this->clean((string)($page['extract']??''));
                    $url=(string)($page['fullurl']??($provider['article_base'].rawurlencode(str_replace(' ','_',$title))));
                    if($title===''||mb_strlen($extract)<120||!$this->permitted($url,$provider['api'])) continue;
                    $id='web:'.substr(hash('sha256',$url),0,24);
                    $results[]=['evidence_id'=>$id,'authority'=>$provider['authority'],'source_type'=>'web_reference',
                        'source'=>$provider['name'].' - '.$title,'title'=>$title,'url'=>$url,'license'=>$provider['license'],
                        'retrieved_at'=>now()->toIso8601String(),'requirement'=>Str::limit($extract,config('lesson_research.max_excerpt_chars',1800),''),
                        'usage'=>'supplementary_only'];
                    if(count($results)>=config('lesson_research.max_results',4)) break 2;
                }
            }catch(\Throwable){ continue; }
        }
        return $results;
    }

    private function permitted(string $url,string $api): bool
    {
        $host=mb_strtolower((string)parse_url($url,PHP_URL_HOST));
        $apiHost=mb_strtolower((string)parse_url($api,PHP_URL_HOST));
        return $host!=='' && hash_equals($apiHost,$host) && !in_array($host,config('lesson_research.blocked_hosts',[]),true)
            && parse_url($url,PHP_URL_SCHEME)==='https';
    }

    private function clean(string $text): string
    {
        return trim(preg_replace('/\s+/u',' ',strip_tags(html_entity_decode($text,ENT_QUOTES|ENT_HTML5,'UTF-8'))));
    }
}
