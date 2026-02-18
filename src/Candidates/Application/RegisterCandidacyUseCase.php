<?php

namespace Src\Candidates\Application;

use Src\Candidates\Application\DTO\RegisterCandidacyRequest;
use Src\Candidates\Domain\Candidate;
use Src\Candidates\Domain\Repositories\CandidateRepository;
use Src\Candidates\Domain\Validators\CandidateValidator;
use Src\Candidates\Domain\Validators\RequiredCVValidator;
use Src\Candidates\Domain\Validators\ValidEmailValidator;
use Src\Candidates\Domain\Validators\MinimumExperienceValidator;

class RegisterCandidacyUseCase
{
    private CandidateValidator $validatorChain;

    public function __construct(
        private readonly CandidateRepository $repository
    ) {
        // Build the validator chain
        $this->validatorChain = new RequiredCVValidator();
        $this->validatorChain
            ->setNext(new ValidEmailValidator())
            ->setNext(new MinimumExperienceValidator());
    }

    public function execute(RegisterCandidacyRequest $request): void
    {
        $candidate = Candidate::register(
            $request->name,
            $request->email,
            $request->yearsOfExperience,
            $request->cvContent,
            $request->cvFilePath,
            $request->primarySpecialty
        );

        $this->validatorChain->validate($candidate);

        $this->repository->save($candidate);
    }
}
