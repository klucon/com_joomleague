<?php
declare(strict_types=1);

/** Structured, fail-closed reporting for disposable acceptance processes. */
final class AuditAssertion extends RuntimeException {}

final class AuditReport
{
    private array $cases=[];
    private bool $finished=false;
    private string $reserve;
    public function __construct(private readonly string $path, private readonly array $required)
    {
        if ($required===[] || count($required)!==count(array_unique($required))) throw new InvalidArgumentException('Unique required cases expected');
        foreach ($required as $id) $this->cases[$id]=['id'=>$id,'status'=>'NOT_RUN','evidenceCount'=>0];
        $this->reserve=str_repeat('x',131072);
        $this->write();
        register_shutdown_function(function():void {
            if ($this->finished) return;
            $this->reserve='';
            foreach ($this->cases as &$case) if (in_array($case['status'],['RUNNING','NOT_RUN'],true)) $case['status']='FAIL';
            $this->write();
        });
    }
    public function run(string $id, callable $test):void
    {
        if (!isset($this->cases[$id]) || $this->cases[$id]['status']!=='NOT_RUN') throw new LogicException('Unknown or duplicate audit case');
        $this->cases[$id]['status']='RUNNING'; $this->write();
        try {
            $count=$test();
            if (!is_int($count) || $count<1) throw new RuntimeException('Positive coverage evidence required');
            $this->cases[$id]=['id'=>$id,'status'=>'PASS','evidenceCount'=>$count];
        } catch (Throwable $error) {
            $this->cases[$id]=['id'=>$id,'status'=>'FAIL','evidenceCount'=>0,'errorType'=>get_debug_type($error),'origin'=>basename($error->getFile()).':'.$error->getLine()];
            if ($error instanceof AuditAssertion) $this->cases[$id]['assertion']=$error->getMessage();
            // Test assertions use fixed messages; do not serialize arbitrary exception payloads.
            fwrite(STDERR,'FAIL: '.$id.' ['.get_debug_type($error)."]\n");
        }
        $this->write();
    }
    public function finish():bool
    {
        foreach ($this->cases as &$case) if ($case['status']==='NOT_RUN') $case['status']='FAIL';
        unset($case);
        $this->finished=true; $this->reserve=''; $report=$this->write();
        foreach ($this->cases as $case) echo $case['status'].': '.$case['id'].' evidence='.$case['evidenceCount']."\n";
        return $report['passed'];
    }
    private function write():array
    {
        $passed=$this->finished && !in_array(false,array_map(static fn(array $case):bool=>$case['status']==='PASS' && $case['evidenceCount']>0,$this->cases),true);
        $report=['schemaVersion'=>1,'finished'=>$this->finished,'passed'=>$passed,'cases'=>array_values($this->cases)];
        $temp=$this->path.'.pending';
        if (file_put_contents($temp,json_encode($report,JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT))===false || !rename($temp,$this->path)) throw new RuntimeException('Audit report write failed');
        return $report;
    }
}
