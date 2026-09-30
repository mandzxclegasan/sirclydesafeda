CREATE TABLE users {
    id int(11) NOT NULL, 
    username varchar(50) NOT NULL,
    password varchar(255) NOT NULL,
    role enum('admin', 'user') NOT NULL DEFAULT 'user'

    INSERT INTO 'users' ('id', 'username', 'password', 'role') VALUES
    (1, 'admin_jane', '12345', 'admin');
    (2, 'student_john', '12345', 'user');
    (4, 'sample1', '12345', 'admin');
    (5, 'sample2', '12345', 'admin');



}