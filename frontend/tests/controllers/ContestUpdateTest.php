<?php

/**
 * Description of UpdateContest
 *
 * @author joemmanuel
 */
class UpdateContestTest extends OmegaupTestCase {
    /**
     * Only update the contest title. Rest should stay the same
     */
    public function testUpdateContestTitle() {
        // Get a contest
        $contestData = ContestsFactory::createContest();

        // Prepare request
        $r = new Request();
        $r['contest_alias'] = $contestData['request']['alias'];

        // Log in with contest director
        $r['auth_token'] = $this->login($contestData['director']);

        // Update title
        $r['title'] = Utils::CreateRandomString();

        // Call API
        $response = ContestController::apiUpdate($r);

        // To validate, we update the title to the original request and send
        // the entire original request to assertContest. Any other parameter
        // should not be modified by Update api
        $contestData['request']['title'] = $r['title'];
        $this->assertContest($contestData['request']);
    }

    /**
     *
     * @expectedException ForbiddenAccessException
     */
    public function testUpdateContestNonDirector() {
        // Get a contest
        $contestData = ContestsFactory::createContest();

        // Prepare request
        $r = new Request();
        $r['contest_alias'] = $contestData['request']['alias'];

        // Log in with contest director
        $r['auth_token'] = $this->login(UserFactory::createUser());

        // Update title
        $r['title'] = Utils::CreateRandomString();

        // Call API
        ContestController::apiUpdate($r);
    }

    /**
     * Update from private to public. Should fail if no problems in contest
     *
     * @expectedException InvalidParameterException
     */
    public function testUpdatePrivateContestToPublicWithoutProblems() {
        // Get a contest
        $contestData = ContestsFactory::createContest(null, 0 /* private */);

        // Prepare request
        $r = new Request();
        $r['contest_alias'] = $contestData['request']['alias'];

        // Log in with contest director
        $r['auth_token'] = $this->login($contestData['director']);

        // Update public
        $r['public'] = 1;

        // Call API
        $response = ContestController::apiUpdate($r);
    }

    /**
     * Update from private to public with problems added
     *
     */
    public function testUpdatePrivateContestToPublicWithProblems() {
        // Get a contest
        $contestData = ContestsFactory::createContest(null, 0 /* private */);

        // Get a problem
        $problemData = ProblemsFactory::createProblem();

        // Add the problem to the contest
        ContestsFactory::addProblemToContest($problemData, $contestData);

        // Prepare request
        $r = new Request();
        $r['contest_alias'] = $contestData['request']['alias'];

        // Log in with contest director
        $r['auth_token'] = $this->login($contestData['director']);

        // Update public
        $r['public'] = 1;

        // Call API
        $response = ContestController::apiUpdate($r);

        $contestData['request']['public'] = $r['public'];
        $this->assertContest($contestData['request']);
    }

     /**
      * Set Recommended flag to a given contest
      */
    public function testSetRecommendedFlag() {
        // Get a contest
        $contestData = ContestsFactory::createContest();

        // Prepare request
        $r = new Request();
        $r['contest_alias'] = $contestData['request']['alias'];

        // Log in with site admin
        $r['auth_token'] = $this->login(UserFactory::createAdminUser());

        // Update value to TRUE
        $r['value'] = 1;

        // Call API
        ContestController::apiSetRecommended($r);

        // Verify setting
        $contestData['request']['recommended'] = $r['value'];
        $this->assertContest($contestData['request']);

        // Turn flag down
        $r['value'] = 0;

        // Call API again
        ContestController::apiSetRecommended($r);

        // Verify setting
        $contestData['request']['recommended'] = $r['value'];
        $this->assertContest($contestData['request']);
    }

     /**
      * Set Recommended flag to a given contest from non admin account
      *
      * @expectedException ForbiddenAccessException
      */
    public function testSetRecommendedFlagNonAdmin() {
        // Get a contest
        $contestData = ContestsFactory::createContest();

        // Prepare request
        $r = new Request();
        $r['contest_alias'] = $contestData['request']['alias'];

        // Log in with contest owner
        $r['auth_token'] = $this->login($contestData['director']);

        // Update value to TRUE
        $r['value'] = 1;

        // Call API
        ContestController::apiSetRecommended($r);
    }

    /**
     * Contest length can't be too long
     *
     * @expectedException InvalidParameterException
     */
    public function testUpdateContestLengthTooLong() {
        // Get a contest
        $contestData = ContestsFactory::createContest();

        // Prepare request
        $r = new Request();
        $r['contest_alias'] = $contestData['request']['alias'];

        // Log in with contest director
        $r['auth_token'] = $this->login($contestData['director']);

        // Update length
        $r['finish_time'] = $r['start_time'] + (60 * 60 * 24 * 32);

        // Call API
        $response = ContestController::apiUpdate($r);
    }

    /**
     * Contest can't be updated if already contains runs
     *
     * @expectedException InvalidParameterException
     */
    public function testUpdateContestLengthWithRuns() {
        // Get a contest
        $contestData = ContestsFactory::createContest();

        // STEP 1: Create a problem and add it to the contest
        // Get a problem
        $problemData = ProblemsFactory::createProblem();

        // Add the problem to the contest
        ContestsFactory::addProblemToContest($problemData, $contestData);

        // STEP 2: Get contestant ready to create a run
        // Create our contestant
        $contestant = UserFactory::createUser();

        // Our contestant has to open the contest before sending a run
        ContestsFactory::openContest($contestData, $contestant);

        // Then we need to open the problem
        ContestsFactory::openProblemInContest($contestData, $problemData, $contestant);

        // STEP 3: Send a new run
        // Create an empty request
        $r = new Request();

        // Log in as contestant
        $r['auth_token'] = $this->login($contestant);

        // Build request
        $r['contest_alias'] = $contestData['request']['alias'];
        $r['problem_alias'] = $problemData['request']['alias'];
        $r['language'] = 'c';
        $r['source'] = "#include <stdio.h>\nint main() { printf(\"3\"); return 0; }";

        RunController::apiCreate($r);

        // STEP 4: Update the contest length, this should fail
        // Prepare request
        $r = new Request();
        $r['contest_alias'] = $contestData['request']['alias'];

        // Log in with contest director
        $r['auth_token'] = $this->login($contestData['director']);

        // Update length
        $r['finish_time'] = $r['start_time'] + (60 * 60 * 24);

        // Call API
        $response = ContestController::apiUpdate($r);
    }
}
