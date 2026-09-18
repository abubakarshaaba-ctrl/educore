@props(['text'])

<div {{ $attributes->class(['edu-rich-text']) }}>{!! \App\Support\EduCoreRichText::render($text) !!}</div>

@once
<style>
.edu-rich-text{line-height:1.6;overflow-wrap:anywhere}
.edu-rich-text h1,.edu-rich-text h2,.edu-rich-text h3{color:inherit;margin:.65em 0 .3em;line-height:1.25}
.edu-rich-text h1:first-child,.edu-rich-text h2:first-child,.edu-rich-text h3:first-child{margin-top:0}
.edu-rich-text h1{font-size:1.45em;font-weight:800}
.edu-rich-text h2{font-size:1.25em;font-weight:800}
.edu-rich-text h3{font-size:1.1em;font-weight:700}
.edu-rich-text p{margin:.2em 0}
.edu-rich-text ul,.edu-rich-text ol{margin:.35em 0;padding-left:1.4em}
.edu-rich-text li{margin:.15em 0}
.edu-rich-text a{color:#2563EB;text-decoration:underline;text-underline-offset:2px}
.edu-rich-spacer{height:.55em}
</style>
@endonce
