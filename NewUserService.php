<?php

class NewUserService extends UserService
{

 /**
     * Creates a new user in the system
     * @param User $user User to be created
     * @return returns newly created user
     */
    public function create(User $user)
    {
        // Save new user
        $userMapper = $this->_getMapper();
        $user->password = md5($user->password);
        $user->confirmCode = $this->createToken();
        $user->status = 'new';
        $userId = $userMapper->save($user);
        // Create user's privacy record
        $privacy = new Privacy();
        $privacy->userId = $userId;
        $privacy->videoComment = true;
        $privacy->newMessage = true;
        $privacy->newVideo = true;
        $privacy->videoReady = true;
        $privacy->commentReply = true;
        $privacyMapper = new PrivacyMapper();
        $privacyMapper->save($privacy);
        // Create user's favorites playlist
        $playlistMapper = new PlaylistMapper();
        $favorites = new Playlist();
        $favorites->userId = $userId;
        $favorites->public = false;
        $favorites->type = 'favorites';
        $playlistMapper->save($favorites);
        // Create user's watch later playlist
        $watchLater = new Playlist();
        $watchLater->userId = $userId;
        $watchLater->public = false;
        $watchLater->type = 'watch_later';
        $playlistMapper->save($watchLater);
        return $userMapper->getUserById($userId);
    }

	/**
     * Retrieve instance of User mapper
     * @return UserMapper Mapper is returned
     */
    protected function _getMapper()
    {
        return new UserReMapper();
    }

}
