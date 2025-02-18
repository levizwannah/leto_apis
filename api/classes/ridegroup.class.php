<?php

    /**
     * This class contains the route groups from the database.
     * It is different from the zippedRoute in that it has no access to the real routes
     * meta data such as distance. It only contains route ids and group id.
     */
 

    class RideGroup{
        private $id,
                $routeIds,
                $driverId,
                $createdOn,
                $updatedOn,
                $startLongitude,
                $startLatitude,
                $endLongitude,
                $endLatitude,
                $numOfRiders,
                $completed,
                $dateCompleted,
                $closed,
                $isPrivate,  # this is immutable
                $isAssignable;

        const GRP_TABLE = "ride_group",
              GRP_TABLE_ID = "`ride_group`.`id`",
              GROUP = "group",
              FARE_INC_PCNT = 0.3;

        /**
         * All parameters are optional
         * A group is created with now id, you must call the 
         * RideGroup#loadFromRide($routeId) or RideGroup#loadFromGroup($groupId) to load 
         * group from the database.
         * @param int $id - The id from which the group data should be loaded. This could either be a routeId or a groupId.
         * @param string $from - Tells the constructor the type of id that was passed. Could take values RideGroup::GROUP or RideGroup::ROUTE
         * 
         */
        public function __construct($id = 0, $from = RideGroup::GROUP){
            $this->routeIds = [];
            if($id > 0){
                switch($from){
                    case RideGroup::GROUP:
                        {
                            $this->loadFromGroup($id);
                            break;
                        }
                    case Route::ROUTE:
                        {
                            $this->loadFromRide($id);
                            break;
                        }
                }
            }

        }

        /**
         * Loads the group information from a route that is a member of it.
         * @param int $routeId - The route whose group you want to create.
         */
        public function loadFromRide($routeId){
            $dbManager = new DbManager();

            $dbManager->setFetchAll(true);

            $rideInfo = $dbManager->query(Ride::RIDE_TABLE, ["routeId, groupId"], "routeId = ? and groupId = (SELECT groupId from ". Ride::RIDE_TABLE. " where routeId = ?)", [$routeId, $routeId]);
    
            $dbManager->setFetchAll(false);

            if($rideInfo === false || count($rideInfo) < 1){
                return false;
            }

            $groupId = $rideInfo[0]["groupId"];

            $groupInfo = $dbManager->query(RideGroup::GRP_TABLE, ["*"], "groupId = ?", [$groupId]);

            if($groupInfo === false){
                return false;
            }

            $this->setId($groupId);
            $this->populateRids($rideInfo);
            $this->setProperties($groupInfo);
            
            return true;
        }

        /**
         * Loads the group information from a group id in the database.
         * @param int $groupId - The Id of the group that you want to create.
         */
        public function loadFromGroup($groupId){
            $dbManager = new DbManager();

            $groupInfo = $dbManager->query(RideGroup::GRP_TABLE, ["*"], RideGroup::GRP_TABLE_ID. " = ?", [$groupId]);

            if($groupInfo === false){
                return false;
            }

            $dbManager->setFetchAll(true);

            $rideInfo = $dbManager->query(Ride::RIDE_TABLE, ["routeId, groupId"], " groupId = ?", [$groupId]);

            $dbManager->setFetchAll(false);

            if($rideInfo === false || count($rideInfo) < 1){
                return false;
            }


            $this->setId($groupId);
            $this->populateRids($rideInfo);
            $this->setProperties($groupInfo);
      
            return true;
        }

        /**
         * populates the routeIds array
         * @param array $arrayOfAssocArray - the array that contains the database result
         * of route ids
         */
        function populateRids($arrayOfAssocArray){
            $this->routeIds = [];

            foreach($arrayOfAssocArray as $rideInfo){
                $this->routeIds[] = $rideInfo["routeId"];
            }
        }

        private function setProperties($groupInfo){
            $this->setStartLatitude($groupInfo["s_lat"]);
            $this->setStartLongitude($groupInfo["s_long"]);
            $this->setEndLatitude($groupInfo["e_lat"]);
            $this->setEndLongitude($groupInfo["e_long"]);
            $this->setNumOfRiders($groupInfo["num_riders"]);
            $this->setCompleted($groupInfo["completed"]);
            $this->setDateCompleted($groupInfo["completed_on"]);
            $this->hasClosed($groupInfo["is_closed"], true);
            $this->setCreatedOn($groupInfo["created_on"]);
            $this->setUpdatedOn($groupInfo["updated_on"]);
        }

        /**
         * 
         */
        public static function makeNewGroup($sLat, $sLong, $eLat, $eLong, $numOfRiders){
            $dbManager = new DbManager();

            $groupId = $dbManager->insert(RideGroup::GRP_TABLE, ["s_long", "s_lat","e_long","e_lat","num_riders", "driverId"], [$sLong, $sLat, $eLong, $eLat, $numOfRiders, null]);

            if($groupId == -1){
                return -1;
            }
            
            return $groupId;
        }

        /**
         * 
         * Make a new ride and assign the group id to it.
         */
        public static function makeAndGroupRide($groupId, $riderId, $firstInGroup = false){
            $group = new RideGroup($groupId);
            if($group->getNumberOfRides() >= Vehicle::getMaxCapacity()){
                return false;
            }

            $rideId = RideFactory::makeRide($riderId, $groupId, $firstInGroup);
            
            if($rideId == -1){
                return false;
            }

            return $rideId;
        }


        /**
         * @param int $fare - the fare to divide amoungst the member.
         * The fare increases by 25%
         */
        public function distributeFare($fare){
            return round(
                (
                    $fare * 
                    (
                        1 + 
                        (
                        count($this->routeIds) - 1
                        ) * RideGroup::FARE_INC_PCNT
                    )
                )/count($this->routeIds)
            );
        }

        /**
         * This function assigns a driver to the group
         */
        public function assignDriver($driverId){
            if(empty($this->id)) return false;

            $dbManager = new DbManager();
            return $dbManager->update(RideGroup::GRP_TABLE, "driverId = ?", [$driverId], RideGroup::GRP_TABLE_ID." = ?", [$this->id]);
        }

        /**
         * Removes a ride from the group
         */
        public function removeRide($id, $idType = Ride::RIDE){
            $dbManager = new DbManager();
            $routeId = $id;

            if($idType == Ride::RIDE){
                $ride = new Ride($id);
                $routeId = $ride->getRouteId();       
            }
            if($dbManager->delete(Route::ROUTE_TABLE, Route::ROUTE_TABLE_ID." = ?", [$routeId])){
                $new = [];
                foreach($this->routeIds as $key => $rid){
                    if($rid != $routeId){
                        $new[] = $routeId;
                    }
                }
                $this->routeIds = $new;
                
                return true;
            }
            return false;
        }

        /**
         * Resets the group start and end lat and long
         * if the first person who created the group is 
         * no longer a member of the group and the group is not yet 
         * assigned to a driver.
         */
        public function resetGeoCoord($sLat, $sLong, $eLat, $eLong){

        }
        /**
         * Get the value of id
         */ 
        public function getId()
        {
                return $this->id;
        }

        /**
         * Set the value of id
         *
         * @return  self
         */ 
        public function setId($id)
        {
                $this->id = $id;

                return $this;
        }

        /**
         * Get the value of routeIds
         */ 
        public function getRouteIds()
        {
                        return $this->routeIds;
        }

        /**
         * Set the value of routeIds
         *
         * @return  self
         */ 
        public function setRouteIds($routeIds)
        {
                        $this->routeIds = $routeIds;

                        return $this;
        }

        /**
         * Get the value of driverId
         */ 
        public function getDriverId()
        {
                        return $this->driverId;
        }

        /**
         * Set the value of driverId
         *
         * @return  self
         */ 
        public function setDriverId($driverId)
        {
                        $this->driverId = $driverId;

                        return $this;
        }

        /**
         * Get the value of createdOn
         */ 
        public function getCreatedOn()
        {
                        return $this->createdOn;
        }

        /**
         * Set the value of createdOn
         *
         * @return  self
         */ 
        public function setCreatedOn($createdOn)
        {
                        $this->createdOn = $createdOn;

                        return $this;
        }

        /**
         * Get the value of updatedOn
         */ 
        public function getUpdatedOn()
        {
                        return $this->updatedOn;
        }

        /**
         * Set the value of updatedOn
         *
         * @return  self
         */ 
        public function setUpdatedOn($updatedOn)
        {
                        $this->updatedOn = $updatedOn;

                        return $this;
        }

        

        /**
         * Get the value of startLongitude
         */ 
        public function getStartLongitude()
        {
                        return $this->startLongitude;
        }

        /**
         * Set the value of startLongitude
         *
         * @return  self
         */ 
        public function setStartLongitude($startLongitude)
        {
                        $this->startLongitude = $startLongitude;

                        return $this;
        }

        /**
         * Get the value of startLatitude
         */ 
        public function getStartLatitude()
        {
                        return $this->startLatitude;
        }

        /**
         * Set the value of startLatitude
         *
         * @return  self
         */ 
        public function setStartLatitude($startLatitude)
        {
                        $this->startLatitude = $startLatitude;

                        return $this;
        }

        /**
         * Get the value of endLongitude
         */ 
        public function getEndLongitude()
        {
                        return $this->endLongitude;
        }

        /**
         * Set the value of endLongitude
         *
         * @return  self
         */ 
        public function setEndLongitude($endLongitude)
        {
                        $this->endLongitude = $endLongitude;

                        return $this;
        }

        /**
         * Get the value of endLatitude
         */ 
        public function getEndLatitude()
        {
                        return $this->endLatitude;
        }

        /**
         * Set the value of endLatitude
         *
         * @return  self
         */ 
        public function setEndLatitude($endLatitude)
        {
                        $this->endLatitude = $endLatitude;

                        return $this;
        }

        /**
         * Get the value of numOfRiders
         */ 
        public function getNumOfRiders()
        {
                        return $this->numOfRiders;
        }

        /**
         * Set the value of numOfRiders
         *
         * @return  self
         */ 
        public function setNumOfRiders($numOfRiders)
        {
                        $this->numOfRiders = $numOfRiders;

                        return $this;
        }

        public function getNumberOfRides(){
            return count($this->routeIds);
        }

        /**
         * Get the value of closed
         */ 
        public function isClosed(DbManager $dbManager = null)
        {
            if($dbManager == null) $dbManager = new DbManager();
            $groupInfo = $dbManager->query(RideGroup::GRP_TABLE, ["is_closed"], RideGroup::GRP_TABLE_ID . "= ?", [$this->id]);

            if($groupInfo === false)  return true; //the group no longer exist

            $this->closed = $groupInfo["is_closed"];
            return $this->closed;
        }

        /**
         * Set the value of closed
         * @return  self
         */ 
        public function hasClosed($closed, $internal = false, DbManager $dbManager  = null)
        {   
            $updated = true;
            if(!$internal){
                if($dbManager == null) $dbManager = new DbManager();

                $updated = $dbManager->update(RideGroup::GRP_TABLE, "is_closed = ?", [$closed], RideGroup::GRP_TABLE_ID . "= ?", [$this->id]);    
            }

            if($updated)$this->closed = $closed; 

            return $this;
        }

        /**
         * Get the value of isPrivate
         */ 
        public function getIsPrivate()
        {
            return $this->isPrivate;
        }

        /**
         * Get the value of isAssignable
         */ 
        public function getIsAssignable(DbManager $dbManager = null)
        {   
            if($dbManager == null) $dbManager = new DbManager();
            $groupInfo = $dbManager->query(RideGroup::GRP_TABLE, ["is_assignable"], RideGroup::GRP_TABLE_ID . "= ?", [$this->id]);

            if($groupInfo === false)  return true; //the group no longer exist
            $this->isAssignable = $groupInfo["is_assignable"];
            
            return $this->isAssignable;
        }

        /**
         * Set the value of isAssignable
         * In the database, set internal to true if this is not to be persisted
         * @return  self
         */ 
        public function setIsAssignable(bool $isAssignable, bool $internal = false, DbManager $dbManager = null)
        {   
            $updated = true;
            if(!$internal){
                if($dbManager == null) $dbManager = new DbManager();
                $updated = $dbManager->update(RideGroup::GRP_TABLE, "is_assignable = ?", [$isAssignable], RideGroup::GRP_TABLE_ID ." = ?", [$this->id]);
            }

            if($updated) $this->isAssignable = $isAssignable;
            return $this;
        }

        /**
         * Get the value of completed
         */ 
        public function getCompleted()
        {
                        return $this->completed;
        }

        /**
         * Set the value of completed
         *
         * @return  self
         */ 
        public function setCompleted($completed)
        {
                        $this->completed = $completed;

                        return $this;
        }

        /**
         * Get the value of dateCompleted
         */ 
        public function getDateCompleted()
        {
                        return $this->dateCompleted;
        }

        /**
         * Set the value of dateCompleted
         *
         * @return  self
         */ 
        public function setDateCompleted($dateCompleted)
        {
                        $this->dateCompleted = $dateCompleted;

                        return $this;
        }
    }

?>