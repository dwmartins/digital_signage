import { API_URL } from "@/helpers/constants";
import axios from "axios";

export default {
    /** Retorna as opções necessárias para montar a campanha. */
    async options() {
        return (await axios.get(`${API_URL}/customer/campaign-onboarding/options`)).data;
    },

    /** Envia a contratação, a campanha e suas mídias. */
    async create(data) {
        const payload = new FormData();

        payload.append("plan_id", data.plan_id);
        payload.append("name", data.name);
        payload.append("description", data.description ?? "");
        payload.append("playback_mode", data.playback_mode);
        data.category_ids.forEach((id) => payload.append("category_ids[]", id));
        data.display_point_ids.forEach((id) => payload.append("display_point_ids[]", id));
        data.media_asset_ids.forEach((id) => payload.append("media_asset_ids[]", id));
        data.files.forEach((file) => payload.append("files[]", file));
        data.media_order.forEach((index) => payload.append("media_order[]", index));
        payload.append("media_assignments", JSON.stringify(data.media_assignments));
        payload.append("display_orders", JSON.stringify(data.display_orders));

        return (await axios.post(`${API_URL}/customer/campaign-onboarding`, payload)).data;
    },
};
