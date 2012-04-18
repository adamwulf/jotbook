--
-- Set up a simple table to track The Number
--

CREATE TABLE `counter` (
  `c` bigint(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `counter` VALUES(0);