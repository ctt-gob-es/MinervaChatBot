export default {
    methods: {
        $showError(error) {
            // console.log(error.response.data);
            if(error.response.status == 422){
                let message = error.response.data.errors;
                // console.log(message);
                message = Object.values(message).flat(1);
                // console.log(message);
                message = message.map((el)=>{
                return [`<li>${el}</li>`];
                });
                // console.log(message);
                message = message.join('');
                this.$messageAlert( 'warning', 'Atención!' , '', null, `<p>El formulario tiene los siguientes errores: </p><ul>${message}</ul>`);
            }
        },

        $messageAlert: function(state, title, msj, time = 1500, html = null ) {
            this.$swal({
                icon: state,
                title: title,
                text: msj,
                timer: time,
                html
            });
        },

        $capitalize: function(str){
            return str.charAt(0).toUpperCase() + str.slice(1);;
        },

        $formatNumber: function(number){
            return new Intl.NumberFormat("de-DE").format(number);
        },

        $getIndex: function(array, element){
            return array.map(function (x) {
                return x.id;
            })
            .indexOf(element);
        },

        $viewFile: function(file, type){
            const searchURL = new URL(`${window.origin}/viewFile`);
            searchURL.searchParams.set('file', file);
            searchURL.searchParams.set('type', type);

            window.open(searchURL.href);

        }
    },
};