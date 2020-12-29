<?php
/**
 * swoole/table 是一个基于共享内存和锁实现的高性能并发数据结构，可用于解决多进程/多线程数据共享和同步加锁问题：
 * 性能强悍，单线程每秒可读写200万次；
 * 应用代码无需加锁，内置行锁自旋锁，所有操作均是多线程/多进程安全，用户层完全不需要考虑数据同步问题；
 * 支持多进程，可用于多进程之间共享数据；
 * 使用行锁，而不是全局锁，仅当 2 个进程在同一 CPU 时间，并发读取同一条数据才会进行发生抢锁。
 */
use Swoole\Table;
$table = new Table(1024);
//定义列
$table->column('id', Table::TYPE_INT);
$table->column('name', Table::TYPE_STRING, 10);
$table->column('score', Table::TYPE_FLOAT);
$table->create();   //创建table
$table->set('s1', ['id'=>1,'name'=>'test1']);     //保存行
$table->set('s1', ['score'=>12.5]);
$table->set('s2', ['id'=>2,'name'=>'test2','score'=>22.5]);
if($table->exist('s1')){        //是否存在
    echo json_encode($table->get('s1'))."\n";   //获取整行
}
$table->incr('s2', 'score', 2);     //自增
echo 's2 incr score : '.$table->get('s2', 'score')."\n";        //获取单个字段
$table->decr('s2', 'score', 3);     //自减
echo 's2 decr score : '.$table->get('s2', 'score')."\n";
echo 'table count:'.$table->count()."\n";   //获取行数
$table->del("s1");      //删除行
var_dump($table->get('s1'));
