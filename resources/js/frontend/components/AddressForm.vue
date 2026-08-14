<script setup>
import { ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import axios from "axios";
import { useI18n } from "vue-i18n";
import Button from "./UI/Button.vue";
import FormInput from "./UI/FormInput.vue";

const props = defineProps({ provinces: { type: Array, default: () => [] }, address: { type: Object, default: null }, submitLabel: String });
const emit = defineEmits(["saved", "cancel"]);
const { t } = useI18n();
const districts = ref([]),
    subDistricts = ref([]),
    villages = ref([]);
const form = useForm({
    name: "",
    phone: "",
    province_id: "",
    district_id: "",
    sub_district_id: "",
    village_id: "",
    address: "",
    postal_code: "",
    is_featured: false,
});
const reset = () => {
    form.reset();
    form.clearErrors();
    districts.value = [];
    subDistricts.value = [];
    villages.value = [];
    if (props.address) Object.assign(form, { ...props.address, is_featured: !!props.address.is_featured });
};
watch(() => props.address, reset, { immediate: true });
const loadDistricts = async (keep = false) => {
    if (!form.province_id) return;
    districts.value = (await axios.get(route("frontend.regions.districts", form.province_id))).data;
    if (!keep) {
        form.district_id = "";
        form.sub_district_id = "";
        form.village_id = "";
        subDistricts.value = [];
        villages.value = [];
    }
};
const loadSubDistricts = async (keep = false) => {
    if (!form.district_id) return;
    subDistricts.value = (await axios.get(route("frontend.regions.sub-districts", form.district_id))).data;
    if (!keep) {
        form.sub_district_id = "";
        form.village_id = "";
        villages.value = [];
    }
};
const loadVillages = async (keep = false) => {
    if (!form.sub_district_id) return;
    villages.value = (await axios.get(route("frontend.regions.villages", form.sub_district_id))).data;
    if (!keep) form.village_id = "";
};
watch(
    () => props.address,
    async (address) => {
        if (!address) return;
        await loadDistricts(true);
        await loadSubDistricts(true);
        await loadVillages(true);
    },
);
const villageChanged = () => {
    const village = villages.value.find((item) => String(item.id) === String(form.village_id));
    if (village?.postal_code) form.postal_code = village.postal_code;
};
const sanitizePhone = (value) => {
    form.phone = String(value || "")
        .replace(/\D/g, "")
        .slice(0, 15);
};
const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => emit("saved", { ...form.data() }) };
    if (props.address?.id) form.patch(route("frontend.account.address.update", props.address.id), options);
    else form.post(route("frontend.account.address.store"), options);
};
</script>
<template>
    <form class="space-y-5" @submit.prevent="submit">
        <div class="grid gap-4 md:grid-cols-2">
            <label class="text-foreground space-y-2 text-sm font-semibold"
                >{{ t("labels.address.fields.name") }}<FormInput v-model="form.name" :invalid="Boolean(form.errors.name)" /><span
                    v-if="form.errors.name"
                    class="text-destructive text-xs font-medium"
                    >{{ form.errors.name }}</span
                ></label
            ><label class="text-foreground space-y-2 text-sm font-semibold"
                >{{ t("labels.address.fields.phone")
                }}<FormInput
                    :model-value="form.phone"
                    maxlength="15"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    autocomplete="tel"
                    :invalid="Boolean(form.errors.phone)"
                    @update:model-value="sanitizePhone"
                /><span v-if="form.errors.phone" class="text-destructive text-xs font-medium">{{ form.errors.phone }}</span></label
            >
        </div>
        <label class="text-foreground block space-y-2 text-sm font-semibold"
            >{{ t("labels.address.fields.address")
            }}<textarea
                v-model="form.address"
                rows="3"
                class="border-border focus:border-primary focus:ring-primary/25 bg-secondary w-full rounded-xl border px-4 py-3 text-sm outline-none focus:ring-2"
            /><span v-if="form.errors.address" class="text-destructive text-xs font-medium">{{ form.errors.address }}</span></label
        >
        <div class="grid gap-4 md:grid-cols-2">
            <label class="text-foreground space-y-2 text-sm font-semibold"
                >{{ t("labels.address.fields.province")
                }}<select
                    v-model="form.province_id"
                    class="border-border bg-secondary w-full rounded-xl border px-4 py-3 text-sm"
                    @change="loadDistricts()"
                >
                    <option value="">{{ t("placeholders.select_province") }}</option>
                    <option v-for="item in provinces" :key="item.id" :value="item.id">{{ item.name }}</option></select
                ><span v-if="form.errors.province_id" class="text-destructive text-xs font-medium">{{ form.errors.province_id }}</span></label
            ><label class="text-foreground space-y-2 text-sm font-semibold"
                >{{ t("labels.address.fields.district")
                }}<select
                    v-model="form.district_id"
                    class="border-border bg-secondary w-full rounded-xl border px-4 py-3 text-sm"
                    @change="loadSubDistricts()"
                >
                    <option value="">{{ t("placeholders.select_district") }}</option>
                    <option v-for="item in districts" :key="item.id" :value="item.id">{{ item.name }}</option></select
                ><span v-if="form.errors.district_id" class="text-destructive text-xs font-medium">{{ form.errors.district_id }}</span></label
            ><label class="text-foreground space-y-2 text-sm font-semibold"
                >{{ t("labels.address.fields.sub_district")
                }}<select
                    v-model="form.sub_district_id"
                    class="border-border bg-secondary w-full rounded-xl border px-4 py-3 text-sm"
                    @change="loadVillages()"
                >
                    <option value="">{{ t("placeholders.select_sub_district") }}</option>
                    <option v-for="item in subDistricts" :key="item.id" :value="item.id">{{ item.name }}</option></select
                ><span v-if="form.errors.sub_district_id" class="text-destructive text-xs font-medium">{{ form.errors.sub_district_id }}</span></label
            ><label class="text-foreground space-y-2 text-sm font-semibold"
                >{{ t("labels.address.fields.village")
                }}<select
                    v-model="form.village_id"
                    class="border-border bg-secondary w-full rounded-xl border px-4 py-3 text-sm"
                    @change="villageChanged"
                >
                    <option value="">{{ t("placeholders.select_village") }}</option>
                    <option v-for="item in villages" :key="item.id" :value="item.id">{{ item.name }}</option></select
                ><span v-if="form.errors.village_id" class="text-destructive text-xs font-medium">{{ form.errors.village_id }}</span></label
            >
        </div>
        <label class="text-foreground block space-y-2 text-sm font-semibold"
            >{{ t("labels.address.fields.postal_code") }}<FormInput v-model="form.postal_code" :invalid="Boolean(form.errors.postal_code)" /><span
                v-if="form.errors.postal_code"
                class="text-destructive text-xs font-medium"
                >{{ form.errors.postal_code }}</span
            ></label
        ><label class="text-foreground flex items-center gap-3 text-sm font-semibold"
            ><input v-model="form.is_featured" type="checkbox" class="border-border text-primary focus:ring-primary h-4 w-4 rounded" />{{
                t("labels.address.set_default")
            }}</label
        >
        <div class="flex flex-wrap justify-end gap-3">
            <Button type="button" variant="outline" @click="emit('cancel')">{{ t("labels.actions.cancel") }}</Button
            ><Button type="submit" variant="primary" :loading="form.processing">{{ submitLabel || t("labels.actions.save") }}</Button>
        </div>
    </form>
</template>
