let site_title = 'Lampa'

class Utils{
	static trimed(text, max = 50){
		if(text.length > max){
			text = '..' + text.slice(text.length - max)
		}

		return text
	}

	static jsonParse(string, empty = {}){
		let retult = empty

		try{
			retult = JSON.parse(string)
		}
		catch(e){ }
		
		return retult
	}
}

class Socket{
	constructor(){
		try{
	        this.socket = new WebSocket('wss://cub.watch:8020')

	        this.socket.addEventListener('open', ()=>{
	        	this.send('start',{})

	        	this.onOpen()
	        })

	        this.socket.addEventListener('message', (event)=> {
	        	var result = Utils.jsonParse(event.data)

	        	if(result.method) this.onMessage(result)
	        })
	    }
	    catch(e){
	        console.log('Socket','not work')
	    }

	    window.addEventListener('beforeunload', this.destroy)
	}

	send(method, data){
	    data.device_id = 'lampa-cast'
	    data.name      = 'CUB'
	    data.method    = method
	    data.version   = 1
	    data.account   = {}
	    data.premium   = 0

	    if(this.socket && this.socket.readyState == 1) this.socket.send(JSON.stringify(data))
	}

	destroy(){
		window.removeEventListener('beforeunload',this.destroy)

		if(this.socket) this.socket.close()
	}
}

class Decives{
	constructor(url){
		this.url   = url
		this.modal = $(`<div class="devices">

			<div class="devices__scan">
				<div></div>
			</div>

			<div class="devices__list"></div>
		</div>`)

		this.modal.on('click',(e)=>{
			if(!$(e.target).closest('.device').length) this.destroy()
		})

		this.socket = new Socket()

		this.socket.onOpen    = this.starScan.bind(this)
		this.socket.onMessage = this.update.bind(this)

		$('body').append(this.modal)
	}

	starScan(){
		this.socket.send('devices',{})

		this.timer = setInterval(()=>{
			this.socket.send('devices',{})
		},5000)
	}

	update(result){
		if(result.method == 'devices'){
            let devices = result.data.filter(d => d.name !== 'CUB')
            
            this.finded(devices)
        }
	}

	finded(devices){
		this.modal.find('.devices__list').empty()

		devices.forEach(device=>{
			let name = device.name || 'Неизвестно'
			let item = $(`<div class="device">
			    <style>display: inline</style>
				<span class="device__name">${name}</span>
                <span class="device__send"></span>
					<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" x="0" y="0" viewBox="0 0 24 24" xml:space="preserve"><path d="M22.23 12c0 .73-.471 1.38-1.29 1.79L4.52 22c-.4.2-.79.3-1.15.3-.521 0-.98-.22-1.271-.609-.249-.341-.489-.931-.199-1.891l1.85-6.17c.06-.18.1-.399.12-.63H14c.55 0 1-.45 1-1s-.45-1-1-1H3.87a2.938 2.938 0 0 0-.12-.63L1.9 4.2c-.29-.96-.05-1.55.2-1.89.49-.66 1.42-.81 2.42-.31l16.421 8.21c.819.41 1.289 1.06 1.289 1.79z" fill="currentColor"></path></svg>
				</div>
				
			</div>`)

			item.on('click',()=>{
				let play = {
                	title: site_title,
                	url: this.url
                }

				this.socket.send('other',{
					params: {
						submethod: 'play',
	                    object: {
	                        player: play,
	                        playlist: [play]
	                    }
					},
                    uid: device.uid
                })

                this.destroy()
			})

			this.modal.find('.devices__list').prepend(item)
		})
	}

	destroy(){
		this.modal.remove()

		clearInterval(this.timer)

		this.socket.destroy()
	}
}


class Item{
	constructor(url){
		this.item = $(`<div class="url">
			<div class="url__icon">
				<svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:svgjs="http://svgjs.com/svgjs" width="20" height="20" x="0" y="0" viewBox="0 0 32 32" xml:space="preserve"><path d="M26.17 12.37 9 2.45A3.23 3.23 0 0 0 7.38 2 3.38 3.38 0 0 0 4 5.38v21.29a3.33 3.33 0 0 0 5.1 2.82l17.19-10.86a3.65 3.65 0 0 0-.12-6.26z" fill="currentColor"></path></svg>
			</div>
			<div class="url__body">
				<div class="url__link"></div>
				<div class="url__exe"></div>
			</div>
		</div>`)

		let exe = '.none'
		let exe_list = ['.m3u8','.mpd','.mp4','.mkv']

		exe_list.forEach(need=>{
			if(url.split('/').pop().indexOf(need) !== -1) exe = need
		})

		this.item.find('.url__link').text(Utils.trimed(url))
		this.item.find('.url__exe').text(exe)

		this.item.on('click',()=>{
			new Decives(url)
		})
	}

	render(){
		return this.item
	}
}

$('.custom-url').on('submit',(e)=>{
	e.preventDefault()

	let url = $('.custom-url__input').val().trim()

	if(url) new Decives(url)
})


chrome.runtime.sendMessage({ cmd: 'get' },(response)=>{
	$('.loading').fadeOut(250)

	let domain = (response.site.url || '').replace('http://','').replace('https://','').split('/')[0]
	let title  = response.site.title || 'lampa'

	site_title = 'Lampa'

	$('.head__url').text(domain || '')
	$('.head__title').text(title.length > 30 ? title.slice(0, 30) + '...' : title)

	if(response.links.length){
		response.links.forEach(data=>{
			let item = new Item(data)

			$('.urls').prepend(item.render())
		})
	}
	else{
		$('.urls__empty').toggleClass('hide',false)
	}
})