import { ref } from "vue";
const bus = ref(new Map());

export default function useEventsBus(){

    function emitBus(event, ...args) {
        bus.value.set(event, args);
    }

    return {
        emitBus,
        bus
    }
}
