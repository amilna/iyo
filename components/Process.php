<?php
/* An easy way to keep in track of external processes.
* Ever wanted to execute a process in php, but you still wanted to have somewhat controll of the process ? Well.. This is a way of doing it.
* @compability: Linux and Windows.
* @author: Peec
*/

namespace amilna\iyo\components;

class Process
{
    private $pid;
    private $command;
    public $iswin = false;

    public function __construct($cl=false){
        if (strtoupper(substr(\PHP_OS, 0, 3)) === 'WIN') {
			$iswin = true;
		} else {
			$iswin = false;	
		}
		$this->iswin = $iswin;
        
        if ($cl != false){
            $this->command = $cl;            
            $this->runCom();
        }
    }
    private function runCom(){
        if ($this->iswin)
        {
			$command = $this->command;
			$WshShell = new \COM("WScript.Shell"); 
			$pExec = $WshShell->Run($command, 0, false);
			$pyid = isset($pExec->ProcessID)?$pExec->ProcessID:0;
			if ((int)$pyid > 0)
			{
				$this->pid = $pyid; 
			}
		}
		else
		{
			$command = 'nohup '.$this->command.' > /dev/null 2>&1 & echo $!';                
			exec($command ,$op);
			if (isset($op[0]))
			{
				$this->pid = (int)$op[0];        
			}
		}
    }

    public function setPid($pid){
        $this->pid = $pid;
    }

    public function getPid(){
        return $this->pid;
    }

    public function status(){
        $command = 'ps -p '.$this->pid;
        exec($command,$op);
        if (!isset($op[1]))return false;
        else return true;
    }

    public function start(){
        if ($this->command != '')$this->runCom();
        else return true;
    }

    public function stop(){
        $command = 'kill '.$this->pid;
        exec($command);
        if ($this->status() == false)return true;
        else return false;
    }
}

?>
