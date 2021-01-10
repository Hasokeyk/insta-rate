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
        
        public $cache_path = (__DIR__).'/../cache';
        public $cache_time = 10; //Minute
        
        public function __construct($username = null){
            
            if(!is_dir($this->cache_path)){
                mkdir($this->cache_path);
            }
            
            if($username != null){
                $this->username = $username;
                $this->user     = $this->get_instagram_user($username);
            }
        }
        
        function get_user_posts($username = null, $posts = null){
            
            $username = $username??$this->username;
            
            if($posts == null){
                $this->user = $this->get_instagram_user($username);
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
        
        function get_user_rate($total_likes = null, $total_comments = null, $total_followers = null, $total_views = null, $username = null){
            
            if($username != null and $this->username == null){
                $this->user = $this->get_instagram_user($username);
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
                    'general_rate'    => $this->general_avg_calc(),
                    'influencer_rate' => $this->influencer_avg_calc(),
                    'bussiness_rate'  => $this->bussiness_avg_calc(),
                    'posts_rate'      => $this->get_user_posts(),
                ];
            }
            
            return false;
        }
        
        function influencer_avg_calc($posts = null, $day = 14){
            
            $posts              = $posts??$this->posts;
            $inf_total_likes    = 0;
            $inf_total_comments = 0;
            $inf_total_views    = 0;
            $old_week           = strtotime('-'.$day.' days');
            $now_time           = strtotime('-2 days');
            
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
        
        function general_avg_calc($posts = null, $day = 45){
            
            $posts              = $posts??$this->posts;
            $gnl_total_likes    = 0;
            $gnl_total_comments = 0;
            $gnl_total_views    = 0;
            $old_week           = strtotime('-'.$day.' days');
            $now_time           = time();
            
            foreach($posts as $id => $post){
                
                $likes       = $post->node->edge_media_preview_like->count??0;
                $comments    = $post->node->edge_media_to_comment->count??0;
                $view        = $post->node->video_view_count??0;
                $shared_time = $post->node->taken_at_timestamp??0;
                
                if($shared_time >= $old_week and $shared_time <= $now_time){
                    $gnl_total_likes    += $likes;
                    $gnl_total_comments += $comments;
                    $gnl_total_views    += $view;
                }
            }
            
            return (object) [
                'gnl_total_likes'    => $gnl_total_likes,
                'gnl_total_comments' => $gnl_total_comments,
                'gnl_total_views'    => $gnl_total_views,
                'gnl_like_avg'       => ($gnl_total_likes / $this->total_post),
                'gnl_comment_avg'    => ($gnl_total_comments / $this->total_post),
                'gnl_view_avg'       => ($gnl_total_views / $this->total_post),
                'gnl_rate'           => $this->calc_rate($gnl_total_likes, $gnl_total_comments, $gnl_total_views, $this->followers)??0,
            ];
        }
        
        function bussiness_avg_calc($posts = null, $day = 30){
            
            $posts              = $posts??$this->posts;
            $bsn_total_likes    = 0;
            $bsn_total_comments = 0;
            $bsn_total_views    = 0;
            $old_week           = strtotime('-'.$day.' days');
            $now_time           = strtotime('-7 days');
            
            foreach($posts as $id => $post){
                
                $likes       = $post->node->edge_media_preview_like->count??0;
                $comments    = $post->node->edge_media_to_comment->count??0;
                $view        = $post->node->video_view_count??0;
                $shared_time = $post->node->taken_at_timestamp??0;
                
                if($shared_time >= $old_week and $shared_time <= $now_time){
                    $bsn_total_likes    += $likes;
                    $bsn_total_comments += $comments;
                    $bsn_total_views    += $view;
                }
            }
            
            return (object) [
                'bsn_total_likes'    => $bsn_total_likes,
                'bsn_total_comments' => $bsn_total_comments,
                'bsn_total_views'    => $bsn_total_views,
                'bsn_like_avg'       => ($bsn_total_likes / $this->total_post),
                'bsn_comment_avg'    => ($bsn_total_comments / $this->total_post),
                'bsn_view_avg'       => ($bsn_total_views / $this->total_post),
                'bsn_rate'           => $this->calc_rate($bsn_total_likes, $bsn_total_comments, $bsn_total_views, $this->followers)??0,
            ];
        }
        
        function get_instagram_user($username = null){
            
            $username = $username??$this->username;
            if($username != null){
                
                $cache_file = $this->cache_path.'/'.$username.'.json';
                
                if(file_exists($cache_file) and time() <= strtotime('+'.$this->cache_time.' minute', filemtime($cache_file))){
                    $user_json = file_get_contents($cache_file);
                    $user_json = json_decode($user_json);
                }else{
                    
                    $query_hash_posts     = $this->get_instagram_post_queryhash();
                    $query_hash_followers = $this->get_instagram_media_queryhash();
                    $user_id              = $this->get_instagram_user_id($username);
                    
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
                    
                    file_put_contents($cache_file, json_encode($user_json));
                    
                }
                
                $this->followers  = $user_json->user->edge_followed_by->count??0;
                $this->posts      = $user_json->user->edge_owner_to_timeline_media->edges??0;
                $this->total_post = $user_json->user->edge_owner_to_timeline_media->count??0;
                
                if($this->posts != null){
                    $this->get_user_posts($this->username, $this->posts);
                }
                
                return $user_json;
                
            }
            
            return false;
            
        }
        
        function get_instagram_post_queryhash(){
            $link   = 'https://www.instagram.com/static/bundles/es6/Consumer.js/260e382f5182.js';
            $get_js = file_get_contents($link);
            preg_match('|l.pagination},queryId:"(.*?)"|is', $get_js, $query_hash);
            $this->query_hash = $query_hash[1];
            return $query_hash[1];
        }
        
        function get_instagram_media_queryhash(){
            $link   = 'https://www.instagram.com/static/bundles/es6/Consumer.js/260e382f5182.js';
            $get_js = file_get_contents($link);
            preg_match_all('|const t="(.*?)"|is', $get_js, $query_hash);
            $this->query_hash = $query_hash[1][1];
            return $query_hash[1][1];
        }
        
        function get_instagram_user_id($username = null){
            
            $username = $username??$this->username;
            
            if($username != null){
                $link = 'https://www.instagram.com/web/search/topsearch/?query='.$username;
                
                $json = file_get_contents($link);
                $json = json_decode($json);
                
                $user_id = 0;
                foreach($json->users as $user){
                    if($username == $user->user->username){
                        $user_id = $user->user->pk;
                    }
                }
                
                return $user_id;
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