<?php
    
    
    namespace insta_rate;
    
    class insta_rate{
        
        private $username = null;
        private $posts = null;
        private $total_post = null;
        private $followers = null;
        private $limit = null;
        private $query_hash = null;
        private $user = null;
        
        private $total_likes = null;
        private $total_comments = null;
        private $total_views = null;
        
        public function __construct($username = null){
            if($username != null){
                $this->username = $username;
                $this->user     = $this->get_insta($username);
            }
        }
        
        function get_insta($username = null, $no_post = true){
            
            $username = $this->username??$username;
            $user     = $this->get_user($username);
            
            if($user != false){
                $this->followers  = $user->user->edge_followed_by->count??0;
                $this->posts      = $user->user->edge_owner_to_timeline_media->edges??0;
                $this->total_post = $user->user->edge_owner_to_timeline_media->count??0;
                
                if($no_post == true){
                    $this->get_post();
                }
                
                return $user;
            }
            
            return false;
        }
        
        function get_post($username = null, $posts = null){
            
            $username = $username??$this->username;
            
            if($posts == null){
                $this->user = $this->get_insta($username, false);
            }
            
            $total_likes    = 0;
            $total_comments = 0;
            $total_views    = 0;
            $insta_posts    = [];
            $posts          = $this->posts??$posts;
            $this->limit    = count($posts);
            
            foreach($posts as $id => $post){
                
                $likes    = $post->node->edge_media_preview_like->count??0;
                $comments = $post->node->edge_media_to_comment->count??0;
                $view     = $post->node->video_view_count??0;
                
                $total_likes    += $likes;
                $total_comments += $comments;
                $total_views    += $view;
                
                $insta_posts[] = (object) [
                    'shortcode'      => $post->node->shortcode,
                    'total_likes'    => $likes,
                    'total_comments' => $comments,
                    'total_view'     => $view,
                    'rate'           => $this->calc_rate($likes, $comments, $view, $this->followers)??0,
                ];
                
            }
            
            $this->total_likes    = $total_likes;
            $this->total_comments = $total_comments;
            $this->total_views    = $total_comments;
            
            return (object) [
                'total_likes'    => $total_likes,
                'total_comments' => $total_comments,
                'posts_details'  => (object) $insta_posts,
            ];
            
        }
        
        function user_avg($total_likes = null, $total_comments = null, $total_followers = null, $total_views = null, $username = null){
            
            if($username != null and $this->username == null){
                $this->user = $this->get_insta($username);
            }
            
            $total_likes     = ($total_likes??$this->total_likes);
            $total_comments  = ($total_comments??$this->total_comments);
            $total_followers = ($total_followers??$this->followers);
            $total_views     = ($total_views??$this->total_views);
            
            if($total_likes > 0 or $total_comments > 0){
                
                $like_avg    = $total_likes / $this->total_post;
                $comment_avg = $total_comments / $this->total_post;
                $view_avg    = $total_views / $this->total_views;
                
                return (object) [
                    'like_avg'        => $like_avg,
                    'comment_avg'     => $comment_avg,
                    'view_avg'        => $view_avg,
                    'rate'            => $this->calc_rate($total_likes, $total_comments, $total_views, $total_followers)??0,
                    'influencer_rate' => $this->influencer_calc(),
                ];
            }
            
            return false;
        }
        
        function influencer_calc($posts = null, $day = 14){
            
            $posts              = $posts??$this->posts;
            $inf_total_likes    = 0;
            $inf_total_comments = 0;
            $inf_total_views    = 0;
            $old_week           = strtotime('-'.$day.' days');
            $now_time           = time();
            
            foreach($posts as $id => $post){
                
                $likes       = $post->node->edge_media_preview_like->count??0;
                $comments    = $post->node->edge_media_to_comment->count??0;
                $view        = $post->node->video_view_count??0;
                $shared_time = $post->node->taken_at_timestamp??0;
                
                if($shared_time >= $old_week and $shared_time <= $now_time){
                    $inf_total_likes    += $likes;
                    $inf_total_comments += $comments;
                    $inf_total_views    += $view;
                }
            }
            
            return (object) [
                'inf_total_likes'    => $inf_total_likes,
                'inf_total_comments' => $inf_total_comments,
                'inf_total_views'    => $inf_total_views,
                'inf_like_avg'       => ($inf_total_likes / $this->total_post),
                'inf_comment_avg'    => ($inf_total_comments / $this->total_post),
                'inf_view_avg'       => ($inf_total_views / $this->total_post),
                'inf_rate'           => $this->calc_rate($inf_total_likes, $inf_total_comments, $inf_total_views, $this->followers)??0,
            ];
        }
        
        function get_user($username = null){
            
            $username = $username??$this->username;
            if($username != null){
                
                if(file_exists($username.'.json') and time() <= strtotime('+5 minute',filemtime($username.'.json'))){
                    $user_json = file_get_contents($username.'.json');
                    $user_json = json_decode($user_json);
                }else{
                    
                    $query_hash_posts     = $this->find_query_hash_posts();
                    $query_hash_followers = $this->find_query_hash_followers();
                    $user_id              = $this->find_user_id($username);
                    
                    $link           = 'https://www.instagram.com/graphql/query/?query_hash='.$query_hash_posts.'&variables={"id":"'.$user_id.'","first":50}';
                    $get_posts_json = file_get_contents($link);
                    $get_posts_json = json_decode($get_posts_json);
                    
                    $link               = 'https://www.instagram.com/graphql/query/?query_hash='.$query_hash_followers.'&variables={"id":"'.$user_id.'","first":1}';
                    $get_followers_json = file_get_contents($link);
                    $get_followers_json = json_decode($get_followers_json);
                    
                    $user_json = (object) [
                        'user' => (object) [
                            'edge_owner_to_timeline_media' => $get_posts_json->data->user->edge_owner_to_timeline_media,
                            'edge_followed_by'             => $get_followers_json->data->user->edge_followed_by,
                        ],
                    ];
                    
                    file_put_contents($username.'.json', json_encode($user_json));
                    
                }
                
                return $user_json;
                
            }
            
            return false;
            
        }
        
        function find_query_hash_posts(){
            $link = 'https://www.instagram.com/static/bundles/es6/Consumer.js/260e382f5182.js';
            $get_js = file_get_contents($link);
            preg_match('|l.pagination},queryId:"(.*?)"|is', $get_js, $query_hash);
            $this->query_hash = $query_hash[1];
            return $query_hash[1];
        }
        
        function find_query_hash_followers(){
            $link = 'https://www.instagram.com/static/bundles/es6/Consumer.js/260e382f5182.js';
            $get_js = file_get_contents($link);
            preg_match_all('|const t="(.*?)"|is', $get_js, $query_hash);
            $this->query_hash = $query_hash[1][1];
            return $query_hash[1][1];
        }
        
        function find_user_id($username = null){
            
            $username = $username??$this->username;
            
            if($username != null){
                $link = 'https://www.instagram.com/web/search/topsearch/?query='.$username;
                
                $json = file_get_contents($link);
                $json = json_decode($json);
                
                return $json->users[0]->user->pk;
            }
            
            return false;
            
        }
        
        function calc_rate($likes = 0, $comments = 0, $view = 0, $followers = 0){
            try{
                $calc = @((($likes + $comments + $view) / $followers) * 100);
            }
            catch(DivisionByZeroError $e){
                $calc = 0;
            }
            catch(ErrorException $e){
                $calc = 1;
            }
            return $calc;
        }
    
        public function request($url, $method = 'GET', $data = [], $add_header = null){
        
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
            if($method == 'POST_JSON'){
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }else if($method == 'PUT'){
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }else{
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            
                if($method == 'POST'){
                    if($add_header == null){
                        $data = http_build_query($data);
                    }
                
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                }
            
            }
        
            $headers = [];
            
            if($add_header != null){
                foreach($add_header as $header){
                    $headers[] = $header;
                }
            }
        
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
            $result = curl_exec($ch);
            if(curl_errno($ch)){
                echo 'Error:'.curl_error($ch);
            }
            curl_close($ch);
        
            return $result;
        
        }
    }