<?php
declare(strict_types=1);
function ensureMessageReads():void{
 static $ready=false;if($ready)return;
 // Additive update: preserves existing users, conversations and configuration.
 sql("CREATE TABLE IF NOT EXISTS message_reads (user_id BIGINT NOT NULL,message_id BIGINT NOT NULL,read_at DATETIME NOT NULL,PRIMARY KEY(user_id,message_id))");$ready=true;
}
function unreadMessages(int $userId,?int $proposalId=null):array{
 ensureMessageReads();$filter=$proposalId===null?'':' AND p.id=?';$params=[$userId,$userId,$userId,$userId];if($proposalId!==null)$params[]=$proposalId;
 return sql("SELECT m.id,m.proposal_id FROM messages m JOIN proposals p ON p.id=m.proposal_id JOIN users a ON a.id=p.sender_id JOIN users b ON b.id=p.recipient_id LEFT JOIN message_reads mr ON mr.message_id=m.id AND mr.user_id=? WHERE m.status='ACTIVE' AND m.sender_id<>? AND mr.message_id IS NULL AND a.status='ACTIVE' AND b.status='ACTIVE' AND (p.sender_id=? OR (p.recipient_id=? AND p.status<>'PENDING'))".$filter,$params)->fetchAll();
}
function markThreadRead(array $p,array $u,array $messages):void{
 ensureMessageReads();$column=(int)$u['id']===(int)$p['sender_id']?'sender_read_at':'recipient_read_at';
 // A pending message gets its own receipt only after moderation releases it.
 $ids=[];foreach($messages as $m)if($m['status']==='ACTIVE'&&(int)$m['sender_id']!==(int)$u['id'])$ids[]=(int)$m['id'];
 if($ids){
  $placeholders=implode(',',array_fill(0,count($ids),'?'));
  $readIds=sql("SELECT message_id FROM message_reads WHERE user_id=? AND message_id IN ($placeholders)",array_merge([$u['id']],$ids))->fetchAll(PDO::FETCH_COLUMN);
  $unread=array_values(array_diff($ids,$readIds));
  if($unread){$insert=cfg()['db']['driver']==='sqlite'?'INSERT OR IGNORE':'INSERT IGNORE';$params=[];foreach($unread as $id)array_push($params,$u['id'],$id,now());sql("$insert INTO message_reads(user_id,message_id,read_at) VALUES ".implode(',',array_fill(0,count($unread),'(?,?,?)')),$params);}
 }
 if($p[$column]===null)sql("UPDATE proposals SET $column=? WHERE id=?",[now(),$p['id']]);
}
function notificationSnapshot(int $userId):array{
 $messages=unreadMessages($userId);
 $proposals=sql("SELECT p.id FROM proposals p JOIN users a ON a.id=p.sender_id JOIN users b ON b.id=p.recipient_id WHERE p.recipient_id=? AND p.recipient_read_at IS NULL AND p.status='OPEN' AND a.status='ACTIVE' AND b.status='ACTIVE'",[$userId])->fetchAll();
 $threads=[];$events=[];
 foreach($messages as $m){$threads[(int)$m['proposal_id']]=true;$events[]=['key'=>'m-'.(int)$m['id'],'kind'=>'message','thread'=>(int)$m['proposal_id']];}
 foreach($proposals as $p){$threads[(int)$p['id']]=true;$events[]=['key'=>'p-'.(int)$p['id'],'kind'=>'proposal','thread'=>(int)$p['id']];}
 return ['threads'=>count($threads),'messages'=>count($messages),'proposals'=>count($proposals),'events'=>$events];
}
