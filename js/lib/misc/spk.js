var MAX_SPARKLER_PARTICLES = 2500;
			
			var fps = 30,
				
				s_canvas = document.createElement('canvas'),
				s_context = s_canvas.getContext('2d'),
				
				stageWidth = $(document).width(),
				stageHeight = $(document).height(),
				mouseX = 100,
				mouseY = 100,
				
				s_particles = [],
                                s_int,
                                s_run = false;
				
			function initSparkler()
			{
                            
                                // Scroll top
                                window.scrollTo(0,0);
                            
				// CANVAS SET UP
                                $(s_canvas).attr('id', 'sparkler-canvas');
				document.body.appendChild(s_canvas);
				s_canvas.width = stageWidth; 
				s_canvas.height = stageHeight;
				$(s_canvas).fadeIn('slow');
                                
                                                               
				$(window).resize(function()
				{
					s_canvas.width = 10;
					s_canvas.height = 10;
					stageWidth = $(window).width();
					stageHeight = $(window).height();
					s_canvas.width = stageWidth;
					s_canvas.height = stageHeight;
				});
				
                                $(window).off('mousemove');
                                
				$(window).mousemove(function(event)
				{
                                    if (s_run === true){
					mouseX = event.pageX;
					mouseY = event.pageY;
					if(s_particles.length < MAX_SPARKLER_PARTICLES) createSparklerParticle();
                                    }
				});
                                
                                $(s_canvas).off('click');
                                $(s_canvas).on('click', function(){
                                    toggleSparkler();
                                });
				
                                s_run = true;
				s_int = setInterval(onEnterFrame, 1000 / fps);
			}
			
			function createSparklerParticle()
			{
				var particle = {};
				particle.size = 0.5 + (Math.random() * 4);
				particle.color = "#"+((1<<24)*Math.random()|0).toString(16);
				particle.alpha = 1;
				particle.fade = 0.93 + (Math.random() * 0.05);
				particle.x = mouseX;
				particle.y = mouseY;
				particle.vx = 0.5 - Math.random();
				particle.vy = 0.5 - Math.random();
				
				s_particles.push(particle);
			}
			
			function onEnterFrame()
			{
				s_context.clearRect(0, 0, stageWidth, stageHeight);
				
				var particle;
				var i = s_particles.length;
				while(--i > -1)
				{
					particle = s_particles[i];
					
					s_context.fillStyle = particle.color;
					s_context.globalAlpha = particle.alpha;
					s_context.beginPath();
					s_context.arc(particle.x, particle.y, particle.size, 0, Math.PI*2, true);
					s_context.closePath();
					s_context.fill();
					
					particle.x += particle.vx;
					particle.y += particle.vy;
					particle.alpha *= particle.fade;
					
					if(particle.alpha <= 0.01)
					{
						s_particles = s_particles.slice(0,i).concat(s_particles.slice(i+1));
					}
				}
			}
                        
                        function toggleSparkler(){
                            
                            if (s_run === false){
                                
                                $('#sparkler-canvas').fadeIn('slow');
                                initSparkler();
                                
                            } else {
                                
                                s_run = false;
                                clearInterval(s_int);
                                $('#sparkler-canvas').fadeOut('slow');
                                
                            }
                            
                        }