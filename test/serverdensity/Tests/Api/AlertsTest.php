<?php

namespace serverdensity\Tests\Api;

class AlertsTest extends TestCase
{
    protected function getApiClass()
    {
        return 'serverdensity\Api\Alerts';
    }


    /**
    * @test
    */
    public function shouldCreateAlert(){
        $metric = 'system.load.1';
        $comparison = 'gte';
        $value = '50';
        $subjectType = 'device';
        $subject = '1';
        $recipients = array(
            array(
                array(
                    "type" => "user",
                    "id" => "1",
                    "actions" => array(
                        "sms"
                    )
                )
            )
        );

        $wait = array("seconds" => 60);

        $repeat = array("seconds" => 60);

        $tags = '{"device_name": {"type": "eq", "value": "/dev"}}';

        $expectedArray = array(            'metric' => $metric,
            'comparison' => $comparison,
            'value' => $value,
            'scope' => '{"scope":{"type":"'.$subjectType.'","value":"'.$subject.'"}}',
            'tags' => json_encode('{"device_name": {"type": "eq", "value": "/dev"}}'),
        );

        $expectedArray['recipients'] = json_encode($recipients);
        $expectedArray['wait'] = json_encode($wait);
        $expectedArray['repeat'] = json_encode($repeat);


        $api = $this->getApiMock('alerts');
        $api->expects($this->once())
            ->method('post')
            ->with('alerts/v3/configs/', $expectedArray)
            ->will($this->returnValue($expectedArray));

        $this->assertEquals($expectedArray, $api->create($metric, $comparison, $value, $subjectType, $subject, $recipients, $wait, $repeat, $tags));
    }

    /**
    * @test
    */
    public function shouldDeleteAlert(){
        $expectedArray = array('_id' => '1');

        $api = $this->getApiMock('alerts');
        $api->expects($this->once())
            ->method('HTTPdelete')
            ->with('alerts/v3/configs/1')
            ->will($this->returnValue($expectedArray));

        $this->assertEquals($expectedArray, $api->delete('1'));

    }

    /**
    * @test
    */
    public function shouldGetAllAlerts(){
        $expectedArray = array(
            array('_id' => '1', 'section' => 'system'),
            array('_id' => '2', 'section' => 'system')
        );

        $api = $this->getApiMock('alerts');
        $api->expects($this->once())
            ->method('get')
            ->with('alerts/v3/configs/')
            ->will($this->returnValue($expectedArray));

        $this->assertEquals($expectedArray, $api->all());
    }

    /**
    * @test
    */
    public function shouldUpdateAlert(){
        $expectedArray = array('_id' => '2', 'section' => 'system');

        $api = $this->getApiMock('alerts');
        $api->expects($this->once())
            ->method('put')
            ->with('alerts/configs/1', $expectedArray)
            ->will($this->returnValue($expectedArray));

        $this->assertEquals($expectedArray, $api->update('1', $expectedArray));
    }

    /**
    * @test
    */
    public function shouldUpdateAlertWithOtherArray(){
        $input = array('_id' => '2', 'section' => 'system');

        $wait = array("seconds" => 60);
        $otherArray['wait'] = $wait;
        $expectedArray = array('_id' => '2', 'section' => 'system');
        $expectedArray['wait'] = json_encode($wait);

        $api = $this->getApiMock('alerts');
        $api->expects($this->once())
            ->method('put')
            ->with('alerts/configs/1', $expectedArray)
            ->will($this->returnValue($expectedArray));

        $this->assertEquals($expectedArray, $api->update('1', $expectedArray, $otherArray));
    }

   /**
    * @test
    */
    public function shouldGetAlertBySubjectId(){
        $expectedArray = array(
            array('_id' => '1', 'section' => 'system', 'subjectId' => '1'),
            array('_id' => '2', 'section' => 'system', 'subjectId' => '1')
        );

        $inputFilter = array('filter' => '{"scope":{"type":"device","value":"1"}}');

        $api = $this->getApiMock('alerts');
        $api->expects($this->once())
            ->method('get')
            ->with('alerts/v3/configs/', $inputFilter)
            ->will($this->returnValue($expectedArray));

        $this->assertEquals($expectedArray, $api->bySubject('1', 'device'));
    }

    /**
    * @test
    */
    public function shouldGetAlert(){
        $expectedArray = array('_id' => '2', 'section' => 'system');

        $api = $this->getApiMock('alerts');
        $api->expects($this->once())
            ->method('get')
            ->with('alerts/v3/configs/1')
            ->will($this->returnValue($expectedArray));

        $this->assertEquals($expectedArray, $api->view('1'));
    }

    /**
    * @test
    */
    public function shouldGetTriggered(){
        $expectedArray = array(
            array('_id' => '2', 'section' => 'system')
        );

        $params = array(
            'closed' => true,
            'subjectType' => 'device'
        );

        $api = $this->getApiMock('alerts');
        $api->expects($this->once())
            ->method('get')
            ->with('alerts/triggered/', $params)
            ->will($this->returnValue($expectedArray));

        $this->assertEquals($expectedArray, $api->triggered(true, 'device'));
    }

    /**
    * @test
    */
    public function shouldGetTriggeredBySubjectId(){
        $expectedArray = array(
            array('_id' => '2', 'section' => 'system')
        );

        $params = array(
            'closed' => true,
            'subjectType' => 'device'
        );

        $api = $this->getApiMock('alerts');
        $api->expects($this->once())
            ->method('get')
            ->with('alerts/triggered/2', $params)
            ->will($this->returnValue($expectedArray));

        $this->assertEquals($expectedArray, $api->triggered(true, 'device', '2'));
    }

}
