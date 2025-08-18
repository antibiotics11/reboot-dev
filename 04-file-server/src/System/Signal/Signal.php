<?php

namespace RebootDev\FileServer\System\Signal;

enum Signal: int {
  case SIGHUP  = 1;
  case SIGINT  = 2;
  case SIGQUIT = 3;
  case SIGILL  = 4;
  case SIGABRT = 5;
  case SIGKILL = 9;
  case SIGUSR1 = 10;
  case SIGSEGV = 11;
  case SIGUSR2 = 12;
  case SIGPIPE = 13;
  case SIGALRM = 14;
  case SIGTERM = 15;
  case SIGCHLD = 17;
  case SIGCONT = 18;
  case SIGSTOP = 19;
  case SIGTSTP = 20;
  case SIGTTIN = 21;
  case SIGTTOU = 22;
}