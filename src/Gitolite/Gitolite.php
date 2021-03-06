<?php

namespace Gitolite;

/**
 * Gitolite Class
 *
 * Project:   gitolite-php
 * File:      src/Gitolite/Gitolite.php
 *
 * Copyright (C) 2012 Rafael Goulart
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by  the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * @author  Rafael Goulart <rafaelgou@gmail.com>
 * @license GNU Lesser General Public License
 * @link    https://github.com/rafaelgou/gitolite-php
 * see CHANGELOG
 */
class Gitolite
{
    protected $gitRemoteRepositoryURL = null;
    protected $gitLocalRepositoryPath = null;
    protected $gitEmail = null;
    protected $gitUsername = null;
    /**
     * @var PHPGit_Repository
     */
    protected $gitoliteRepository = null;

    protected $users = array();
    protected $teams = array();
    protected $repos = array();

    protected $log = array();

    const GITOLITE_CONF_FILE = 'gitolite.conf';
    const GITOLITE_CONF_DIR  = 'conf/';
    const GITOLITE_KEY_DIR   = 'keydir/';
    const GITOLITE_REPO_DIR  = 'conf/repos/';

    /**
     * Set GitRemoteRepositoryURL
     *
     * @param string $gitRemoteRepositoryURL The remote repository URL
     *
     * @return Gitolite\Gitolite
     */
    public function setGitRemoteRepositoryURL($gitRemoteRepositoryURL)
    {
        $this->gitRemoteRepositoryURL = (string) $gitRemoteRepositoryURL;
        return $this;
    }

    /**
     * Get GitRemoteRepositoryURL
     *
     * @return string
     */
    public function getGitRemoteRepositoryURL()
    {
        return $this->gitRemoteRepositoryURL;
    }

    /**
     * Set GitLocalRepositoryPath
     *
     * @param string $gitLocalRepositoryPath The git local repository Path
     *
     * @return Gitolite\Gitolite
     */
    public function setGitLocalRepositoryPath($gitLocalRepositoryPath)
    {
        $this->gitLocalRepositoryPath = (string) $gitLocalRepositoryPath;
        return $this;
    }

    /**
     * Get GitLocalRepositoryPath
     *
     * @return string
     */
    public function getGitLocalRepositoryPath()
    {
        return $this->gitLocalRepositoryPath;
    }

    /**
     * Set GitEmail
     *
     * @param string $gitEmail The git user email
     *
     * @return Gitolite\Gitolite
     */
    public function setGitEmail($gitEmail)
    {
        $this->gitEmail = (string) $gitEmail;
        return $this;
    }

    /**
     * Get GitEmail
     *
     * @return string
     */
    public function getGitEmail()
    {
        return $this->gitEmail;
    }

    /**
     * Set GitUsername
     *
     * @param string $gitUsername The git user name
     *
     * @return Gitolite\User
     */
    public function setGitUsername($gitUsername)
    {
        $this->gitUsername = (string) $gitUsername;
        return $this;
    }

    /**
     * Get GitUsername
     *
     * @return string
     */
    public function getGitUsername()
    {
        return $this->gitUsername;
    }

    /**
     * Set Repos
     *
     * @param array $repos An array of repositories
     *
     * @return Gitolite\Acl
     */
    public function setRepos(array $repos)
    {
        $this->$repos = $repos;
        return $this;
    }

    /**
     * Get Repos
     *
     * @return array of Repos
     */
    public function getRepos()
    {
        return $this->repos;
    }

    /**
     * Add repo
     *
     * @param string $repo A repository object
     *
     * @return Gitolite\Acl
     */
    public function addRepo(Repo $repo)
    {
        $this->repos[] = $repo;
        return $this;
    }

    /**
     * Set Users
     *
     * @param array $users An array of user objects
     *
     * @return Gitolite\Acl
     */
    public function setUsers(array $users)
    {
        $this->users = array();
        foreach ($users as $user) {
            $this->addUser($user);
        }
        return $this;
    }

    /**
     * Get Users
     *
     * @return array of Users
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Add user
     *
     * @param string $user A user object
     *
     * @return Gitolite\Acl
     */
    public function addUser(User $user)
    {
        $this->users[] = $user;
        return $this;
    }

    /**
     * Set Teams
     *
     * @param array $teams An array of team objects
     *
     * @return Gitolite\Acl
     */
    public function setTeams(array $teams)
    {
        $this->teams = array();
        foreach ($teams as $team) {
            $this->addTeam($team);
        }
        return $this;
    }

    /**
     * Get Teams
     *
     * @return array of Teams
     */
    public function getTeams()
    {
        return $this->teams;
    }

    /**
     * Add Team
     *
     * @param string $team A team object
     *
     * @return Gitolite\Acl
     */
    public function addTeam(Team $team)
    {
        $this->teams[] = $team;
        return $this;
    }

    /**
     * Get PHPGit_Repository
     *
     * @return PHPGit_Repository
     */
    protected function getGitoliteRepository()
    {
        if (null === $this->gitoliteRepository) {
            if (null === $this->getGitLocalRepositoryPath()) {
                throw new \Exception('Git local repository path not defined');
            }
            try {
                $this->gitoliteRepository = new \PHPGit_Repository($this->getGitLocalRepositoryPath());
            } catch (\Exception $exc) {

                if (file_exists($this->getGitLocalRepositoryPath())) {
                    throw new \Exception("Directory {$this->getGitLocalRepositoryPath()} already exists, impossible to create repository");
                } else {
                    if (mkdir($this->getGitLocalRepositoryPath(), 0770)) {
                        $this->gitoliteRepository = \PHPGit_Repository::create($this->getGitLocalRepositoryPath());
                    } else {
                        throw new \Exception('Impossible to create Directory informed in Git local repository (possibly).');
                    }
                }
            }
        }
        return $this->gitoliteRepository;
    }

    /**
     * Write a File down to disk
     *
     * @param string  $filename    The file to be write to disk
     * @param string  $data        The content to be write
     * @param boolean $checkChange Wheter check or not if data is changed
     *
     * @return string
     */
    protected function writeFile($filename, $data, $checkChange=true)
    {
        if (!file_exists($filename)) {
            if (!file_put_contents($filename, $data)) {
                throw new \Exception("Impossible to write file {$filename}", 1);
            }
        } else {
            if (!$checkChange) {
                if (!file_put_contents($filename, $data)) {
                    throw new \Exception("Impossible to write file {$filename}", 1);
                }
            } else {
                if ($data != file_get_contents($filename)) {
                    file_put_contents($filename, $data);
                }
                return true;
            }
        }
    }

    /**
     * Push configuration to Gitolite Server
     *
     * @return void
     */
    public function pushConfig()
    {
        $cmds[] = 'push origin master';
        $this->runGitCommand($cmds);
    }

    /**
     * Commits changes in configuration
     *
     * @return void
     */
    public function commitConfig()
    {
        $cmds[] = 'add .';
        $cmds[] = 'commit -m "Update configuration from ' .
        $_SERVER['SERVER_NAME'] . ' on ' .date('Y-m-d H:i:s') . '"';
        $this->runGitCommand($cmds);
    }

    /**
     * Write full conf file to disk
     *
     * @return void
     */
    public function writeFullConfFile()
    {
        return $this->writeFile(
            $this->getGitLocalRepositoryPath() . DIRECTORY_SEPARATOR .
            self::GITOLITE_CONF_DIR . self::GITOLITE_CONF_FILE,
            $this->renderFullConfFile()
        );
    }

    /**
     * Write users keys to disk
     *
     * @return void
     */
    public function writeUsers()
    {
        foreach ($this->getUsers() as $user) {
            $this->writeFile(
                $this->getGitLocalRepositoryPath() . DIRECTORY_SEPARATOR .
                self::GITOLITE_KEY_DIR .
                $user->renderKeyFileName(),
                $user->getFirstKey()
            );
        }
    }

    /**
     * Write everything to the disk, commit and push
     *
     * @return void
     */
    public function writeAndPush()
    {
        $this->gitConfig();
        $this->writeFullConfFile();
        $this->writeUsers();
        $this->commitConfig();
        $this->pushConfig();
    }

    /**
     * Return full conf file
     *
     * @return string
     */
    public function renderFullConfFile()
    {
        return $this->renderUserAndTeams() . $this->renderRepos();
    }

    /**
     * Return user and teams for conf file
     *
     * @return string
     */
    public function renderUserAndTeams()
    {
        $return = '';
        foreach ($this->getTeams() as $team) {
            $return .= $team->render() . PHP_EOL;
        }
        return $return;
    }

    /**
     * Return repos for conf file
     *
     * @return string
     */
    public function renderRepos()
    {
        $return = '';
        foreach ($this->getRepos() as $repo) {
            $return .= $repo->render();
        }
        return $return;
    }

    /**
     * Configure the repository
     *
     * @return void
     */
    public function gitConfig()
    {
        $cmds[] = sprintf('config user.name "%s"', $this->getGitUsername());
        $cmds[] = sprintf('config user.email "%s"', $this->getGitEmail());
        $cmds[] = 'remote rm gitoliteorigin';
        $cmds[] = sprintf('remote add gitoliteorigin %s', $this->getGitRemoteRepositoryURL());
        $cmds[] = 'pull origin master';
        $this->runGitCommand($cmds);
    }

    /**
     * Run git commands
     *
     * @param mixed $cmds A command or an array of commands
     *
     * @return string
     */
    protected function runGitCommand($cmds='')
    {
        if (!is_string($cmds) && !is_array($cmds)) {
            return false;
        }

        if (!is_array($cmds)) {
            $cmds = array($cmds);
        }

        foreach ($cmds as $cmd) {
            try {
                $date = date('Y-m-d H:i:s');
                $output = $this->getGitoliteRepository()->git($cmd);
                $this->log("$date COMMAND RUN: git $cmd");
                $this->log("$date OUTPUT : . $output");
            } catch (\GitRuntimeException $e) {
                $this->log("$date GIT ERROR: " . $e->getMessage());
            } catch (\Exception $e) {
                $this->log("$date ERROR: " . $e->getMessage());
            }
        }
    }

    /**
     * Log a message
     *
     * @param type $message The message to log
     *
     * @return void
     */
    protected function log($message)
    {
        $this->log[] = $content;
//        $file = option('root_dir') . '/db/log/' . date('mdY');
//        $handle = fopen($file, 'a+');
//        $content = nl2br($content);
//        fwrite($handle, $content);
//        fwrite($handle, "\n\n\n");
//        fclose($handle);
    }

    /**
     * Get the log
     *
     * @return array
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * Get the log as string
     * 
     * @return string
     */
    public function getLogAsString()
    {
        return implode(PHP_EOL, $this->log);
    }

}