import axios from 'axios';
import moment from "moment";

export const formatDateTime = (dateTimeString) => {
    const momentDateTime = moment.utc(dateTimeString); // Parsear como fecha y hora UTC
    momentDateTime.utcOffset('+02:00');
    const formattedDate = momentDateTime.format("DD/MM/YYYY, HH:mm:ss"); // Formatear como deseado
    return formattedDate;
};

export const getColorSetting = async () => {
    try {
        const response = await axios.get("/getColorSetting");
        return response.data;
    } catch (error) {
        console.error("Error al obtener el color:", error);
        throw error;
    }
};

export const setIdentifier = (unique, value) => {
    const identifier = `select_${unique}`;

    localStorage.setItem(identifier, value);
}

