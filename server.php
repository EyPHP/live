<?php

    //cli_set_process_title("php server.php: master");

	$ws = new swoole_websocket_server('0.0.0.0',9502);
/*	/*$ws->set([
		'daemonize' => 1, //设为守护进程，否则关掉ssh就会退出
	]);*/

    // 开启实例化 redis
	$redis = new Redis();
    	$redis->connect('127.0.0.1', 6379);

    $ws->on('open',function($ws,$request) use($redis){
	    echo "客户端 $request->fd 加入";
        $users = json_decode($redis->get('fd'), true);
        $users['fd'][$request->fd]['id']= $request->fd;
        $users['fd'][$request->fd]['name']= '匿名用户';
         
        $redis->set('fd', json_encode($users));
	
	    //var_dump($redis->get('fd'));
        //$redis->get('fd');
        /*foreach ($ws->connections as $fd) {
            $msg['type'] = 'user'; //用户列表
            $msg['msg'] = '新用户上线了';
            $msg['list'] = json_decode($redis->get('fd'), true);
	        //var_dump(msg);
            $ws->push($fd, json_encode($msg));
        }*/
    });


	$ws->on('message',function($ws,$request) use ($redis){


	    echo '开车';
        $users = json_decode($redis->get('fd'), true);

        $data = json_decode($request->data);

        $msg['type'] = 'message'; // 聊天消息
        $msg['msg'] = '新消息';
        $msg['list'] = $users;
        //var_dump($data);die;

		$msg['name'] = $users['fd'][$request->fd]['name'];
        $msg['data'] = $data->data;
        $msg['myfd'] = $request->fd;

		if($data->type == 'setName'){
            if(strstr($data->data,"#name#")){
                $users['fd'][$request->fd]['name']= str_replace("#name#",'',$data->data);
                $redis->set('fd', json_encode($users));

                // 设置完昵称后重新推送一次
                foreach ($ws->connections as $fd) {
                    $msg['type'] = 'user'; //用户列表
                    $msg['msg'] = '新用户上线了';
                    $msg['list'] = json_decode($redis->get('fd'), true);
                    //var_dump(msg);
                    $ws->push($fd, json_encode($msg));
                }


            }
        }else if($data->type == 'message'){
            foreach($users['fd'] as $i){
                $msg['fd'] = $i['id'];
                $ws->push($i['id'],json_encode($msg));
            }
        }else if($data->type == 'video'){
            $msg['type'] = 'video';
            foreach($users['fd'] as $i){
                $msg['fd'] = $i['id'];
                $ws->push($i['id'],json_encode($msg));
            }
        }
	
	});

	$ws->on('close',function($ws,$request) use($redis){

		echo "客户端-{$request} 断开连接\n";
        $users = json_decode($redis->get('fd'), true);
        $message= '用户'.$users['fd'][$request]['name'].'下线了';

        unset($users['fd'][$request]);

        $redis->set('fd', json_encode($users));

        foreach ($users['fd'] as $fd) {
            $msg['type'] = 'user'; //用户列表
            $msg['msg'] = $message;
            $msg['list'] = json_decode($redis->get('fd'), true);
            //var_dump(msg);
            $ws->push($fd['id'], json_encode($msg));
        }

	});
	
	$ws->start();

























