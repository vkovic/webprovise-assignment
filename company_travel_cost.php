<?php

class CompanyTravelsService
{
    /**
     * @var  Company[]
     */
    protected $companies = [];

    /**
     * @var Travel[]
     */
    protected $travels = [];

    public function __construct(
        protected string $companiesEndpoint,
        protected string $travelsEndpoint) {}

    public function getCompanies()
    {
        // Return from cache
        if ($this->companies) {
            return $this->companies;
        }

        $response = file_get_contents($this->companiesEndpoint);
        $companies = json_decode($response, true);

        // Map companies to DTOs
        foreach ($companies as $company) {
            $this->companies[] = Company::fromArray($company);
        }

        return $this->companies;
    }

    public function getTravels()
    {
        // Return from cache
        if ($this->travels) {
            return $this->travels;
        }

        $response = file_get_contents($this->travelsEndpoint);
        $travels = json_decode($response, true);

        // Map travels to DTOs
        foreach ($travels as $travel) {
            $this->travels[] = Travel::fromArray($travel);
        }

        return $this->travels;
    }

    public function getTravelsByCompanyId($companyId)
    {
        $travelsByCompany = [];

        foreach ($this->getTravels() as $travel) {
            if ($travel->companyId === $companyId) {
                $travelsByCompany[] = $travel;
            }
        }

        return $travelsByCompany;
    }

    public function getCompaniesByParentId($parentId)
    {
        $companiesByParent = [];

        foreach ($this->getCompanies() as $company) {
            if ($company->parentId === $parentId) {
                $companiesByParent[] = $company;
            }
        }

        return $companiesByParent;
    }

    public function getTravelCost($companyId)
    {
        $cost = 0;

        // Cost for the given company
        $travels = $this->getTravelsByCompanyId($companyId);
        foreach ($travels as $travel) {
            $cost += $travel->price;
        }

        // Cost for the nested companies
        $companies = $this->getCompaniesByParentId($companyId);
        foreach ($companies as $company) {
            $childCompanyId = $company->id;
            $childCompanyCost = $this->getTravelCost($childCompanyId);
            $cost += $childCompanyCost;
        }

        return $cost;
    }

    public function getCompanyTree($parentId)
    {
        $tree = [];

        $companies = $this->getCompaniesByParentId($parentId);

        foreach ($companies as $company) {
            $tree[] = [
                'id' => $company->id,
                'createdAt' => $company->createdAt,
                'name' => $company->name,
                'parentId' => $company->parentId,
                'cost' => $this->getTravelCost($company->id),
                'children' => $this->getCompanyTree($company->id),
            ];
        }

        return $tree;
    }
}

class Travel
{
    public function __construct(
        public string $id,
        public float  $price,
        public string $companyId
    ) {}

    public static function fromArray(array $travelArray)
    {
        return new self(
            $travelArray['id'],
            $travelArray['price'],
            $travelArray['companyId'],
        );
    }
}

class Company
{
    public function __construct(
        public string $id,
        public string $createdAt,
        public string $name,
        public string $parentId
    ) {}

    public static function fromArray(array $companyArray)
    {
        return new self(
            $companyArray['id'],
            $companyArray['createdAt'],
            $companyArray['name'],
            $companyArray['parentId'],
        );
    }
}

class TestScript
{
    public function execute()
    {
        $start = microtime(true);

        $companiesEndpoint = 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/companies';
        $travelsEndpoint = 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/travels';
        $companyTravelsService = new CompanyTravelsService($companiesEndpoint, $travelsEndpoint);

        $result = $companyTravelsService->getCompanyTree('0');

        // Enter your code here
        echo json_encode($result);

        echo 'Total time: ' . (microtime(true) - $start);
    }
}


(new TestScript())->execute();
