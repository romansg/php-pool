#
# MySQLpool  database structure
#

create table `job` (
	`id` int(11) unsigned not null AUTO_INCREMENT,
	`data` text not null,
	`status` enum('open','closed') not null default 'open',
	`createdOn` timestamp not null default current_timestamp,
	`finishedOn` timestamp null default null,
	`pendingTasks` int(11) unsigned not null default '0',
	`doneTasks` int(11) unsigned not null default '0',
	`failedTasks` int(11) unsigned not null default '0',
	`deleted` tinyint(1) unsigned not null default '0',
	primary key (`id`)
) engine=myisam;

create table `task` (
	`id` int(11) not null auto_increment,
	`jobid` int(11) unsigned not null default '0',
	`data` text not null,
	`status` enum('pending','done','failed') not null default 'pending',
	`addedon` timestamp not null default current_timestamp,
	`dispatchedon` timestamp null default null,
	`signature` char(16) not null default '',
	primary key (`id`),
	key `job` (`jobid`),
	key `status` (`status`)
) engine=myisam;

delimiter $$

create trigger `taskAdded` after insert on `task`
for each row
update job set pendingtasks = pendingtasks + 1 where id = new.jobid$$

create trigger `taskDispatched` before update on `job`
for each row
if (new.pendingtasks = 0) and (old.pendingtasks > 0) then
	set new.status = 'closed', new.finishedon = current_timestamp();
end if$$

create trigger `taskPerformed` before update on `task`
for each row
if (old.status = 'pending') and (new.status <> 'pending') then
	if (new.status = 'done') then
		update job set pendingtasks = pendingtasks - 1, donetasks = donetasks + 1 where id = new.jobid;
	else
		update job set pendingtasks = pendingtasks - 1, failedtasks = failedtasks + 1 where id = new.jobid;
	end if;

	set new.dispatchedon = current_timestamp();
end if$$

delimiter ;
