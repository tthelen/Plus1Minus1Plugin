CREATE TABLE IF NOT EXISTS `plus1minus1_vote_user` (
  `vote_id` varchar(32) NOT NULL,
  `user_id` varchar(32) NOT NULL,
  `vote` enum('-1','+1') NOT NULL,
  PRIMARY KEY (`vote_id`,`user_id`)
) ENGINE=MyISAM;


