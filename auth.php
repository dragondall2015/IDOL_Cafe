<?php
/* 페이지 최상단에서
   require 'auth.php';                // 일반 로그인만 필요
   require 'auth.php'; requireRole('admin');   // 관리자 권한 필요
*/
session_start();

function requireLogin(){
    if(!isset($_SESSION['uid'])){
        header("Location: login.php"); exit;
    }
}

function requireRole($role){
    requireLogin();
    if($_SESSION['role']!==$role){
        echo "권한 없음"; exit;
    }
}
