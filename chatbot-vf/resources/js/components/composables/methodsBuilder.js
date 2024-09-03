import { ref } from 'vue'

export function emptyObject(object){
    return Object.keys(object).length === 0;
}
