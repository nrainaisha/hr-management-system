<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, useForm} from '@inertiajs/vue3';
import EmployeeTabs from "@/Components/Tabs/EmployeeTabs.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import GenericModal from "@/Components/GenericModal.vue";
import {useToast} from "vue-toastification";
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css'
import {Switch} from "@headlessui/vue";
import Card from "@/Components/Card.vue";
import {inject} from "vue";
import {__} from "@/Composables/useTranslations.js";
import ToolTip from "@/Components/ToolTip.vue";
import dayjs from "dayjs";

const props = defineProps({
    departments: Object,
    branches: Object,
    shifts: Object,
    roles: Object,
})

const form = useForm({
    name: '',
    national_id: '',
    email: '',
    phone: '',
    address: '',
    bank_acc_no: '',
    hired_on: new Date(),
    branch_id: '',
    currency: '',
    salary: '',
    role: '',
});

const shiftForm = useForm({
    name: '',
    start_time: '',
    end_time: '',
    shift_payment_multiplier: '',
    description: '',
});

const submit = () => {
    form.hired_on = dayjs(form.hired_on).format('YYYY-MM-DD');
    form.post(route('employees.store'), {
        preserveScroll: true,
        onError: () => {
            useToast().error(__('Error Creating Employee'));
        },
        onSuccess: () => {
            useToast().success(__('Employee Created Successfully'));
        },
    });
};

const submitShift = () => {
    shiftForm.post(route('shifts.store'), {
        preserveScroll: true,
        onError: () => {
            useToast().error(__('Error Creating Shift'));
        },
        onSuccess: () => {
            useToast().success(__('Shift Created Successfully'));
            document.getElementById('closeShiftModal').click();
            shiftForm.reset();
            form.shift_id = props.shifts.length;
        }
    });
};

</script>

<template>
    <Head :title="__('Employee Register')"/>
    <AuthenticatedLayout>
        <template #tabs>
            <EmployeeTabs/>
        </template>
        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <Card class="!mt-0">
                        <p class="card-header">{{__('Add a New Employee')}}</p>
                        <form @submit.prevent="submit" class="form">
                            <div class="grid grid-cols-2 gap-8">
                                <div>
                                    <InputLabel for="name" :value="__('Full Name')"/>
                                    <TextInput
                                        id="name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        :class="{'border-2 border-red-500': form.errors.name}"
                                        v-model="form.name"
                                        required
                                        autofocus
                                        autocomplete="name"
                                        :placeholder="__('Aina Syafiqah')"
                                    />
                                    <InputError class="mt-2" :message="form.errors.name"/>
                                </div>
                                <div>
                                    <InputLabel for="national_id" :value="__('National ID')"/>
                                    <TextInput
                                        id="national_id"
                                        type="number"
                                        class="mt-1 block w-full"
                                        :class="{'border border-red-500': form.errors.national_id}"
                                        v-model="form.national_id"
                                        required
                                        pattern="[0-9]{14}"
                                        autocomplete="off"
                                        placeholder="040723080179"
                                    />
                                    <InputError class="mt-2" :message="form.errors.national_id"/>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-8 mt-4">
                                <div>
                                    <InputLabel for="phone" :value="__('Phone')"/>
                                    <TextInput
                                        id="phone"
                                        type="text"
                                        class="mt-1 block w-full"
                                        :class="{'border border-red-500': form.errors.phone}"
                                        v-model="form.phone"
                                        required
                                        autocomplete="off"
                                        placeholder="01111659435"
                                    />
                                    <InputError class="mt-2" :message="form.errors.phone"/>
                                </div>
                                <div>
                                    <InputLabel for="email" :value="__('Email')"/>
                                    <TextInput
                                        id="email"
                                        type="email"
                                        class="mt-1 block w-full"
                                        :class="{'border border-red-500': form.errors.email}"
                                        v-model="form.email"
                                        required
                                        autocomplete="off"
                                        placeholder="mat.hensem@mail.com"
                                    />
                                    <InputError class="mt-2" :message="form.errors.email"/>
                                </div>
                            </div>
                            <div class="mt-4">
                                <InputLabel for="address" :value="__('Address')"/>
                                <TextInput
                                    id="address"
                                    type="text"
                                    class="mt-1 block w-full"
                                    :class="{'border border-red-500': form.errors.address}"
                                    v-model="form.address"
                                    required
                                    autocomplete="off"
                                    :placeholder="__('Jalan Plumbum 13 , Taman Plumbum , 13100 , Penang , Malaysia')"
                                />
                                <InputError class="mt-2" :message="form.errors.address"/>
                            </div>
                            <div class="grid grid-cols-2 gap-8 mt-4 ">
                                <div>
                                    <InputLabel for="bank_acc_no" :value="__('Bank Account Number (Optional)')"/>
                                    <TextInput
                                        id="bank_acc_no"
                                        type="text"
                                        class="mt-1 block w-full"
                                        :class="{'border border-red-500': form.errors.bank_acc_no}"
                                        v-model="form.bank_acc_no"
                                        autocomplete="off"
                                        placeholder="162786278449"
                                    />
                                    <InputError class="mt-2" :message="form.errors.bank_acc_no"/>
                                </div>
                                <div>
                                    <InputLabel for="hired_on" :value="__('Hire Date')"/>
                                    <VueDatePicker
                                        id="hired_on"
                                        v-model="form.hired_on"
                                        class="py-1 block w-full"
                                        :class="{'border border-red-500': form.errors.hired_on}"
                                        :enable-time-picker="false"
                                        :placeholder="__('Select a Date...')"
                                        :dark="inject('isDark').value"
                                        required
                                    ></VueDatePicker>
                                    <InputError class="mt-2" :message="form.errors.hired_on"/>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-8 mt-4">
                                <div>
                                    <InputLabel for="salary" :value="__('Salary')" class="mb-1"/>
                                    <div class="grid grid-cols-6">
                                        <select id="currency"
                                                class="fancy-selector-inline-textInput col-span-2 z-10 !mt-0"
                                                v-model="form.currency">
                                            <option value='' selected>Currency</option>
                                            <option value="EGP">EGP</option>
                                            <option value="USD">USD</option>
                                            <option value="EUR">EUR</option>
                                            <option value="GBP">GBP</option>
                                            <option value="CAD">CAD</option>
                                            <option value="SAR">SAR</option>
                                            <option value="AED">AED</option>
                                            <option value="KWD">KWD</option>
                                        </select>
                                        <TextInput
                                            id="salary"
                                            type="number"
                                            class="inline ltr:rounded-l-none rtl:rounded-r-none col-span-4"
                                            :class="{'border border-red-500': form.errors.salary}"
                                            v-model="form.salary"
                                            required
                                            autocomplete="off"
                                            placeholder="50000"
                                        />
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.currency"/>
                                    <InputError class="mt-2" :message="form.errors.salary"/>
                                </div>
                                <div>
                                    <InputLabel for="role" :value="__('Permissions Level')"/>
                                    <select id="role" class="fancy-selector" v-model="form.role">
                                        <option selected value="">{{__('Choose a Permission Level')}}</option>
                                        <option v-for="role in roles" :key="role.id" :value="role.name">
                                            {{ role.name }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.role"/>
                                </div>
                            </div>
                            <div class="flex items-center justify-end mt-4">
                                <PrimaryButton class="ltr:ml-4 rtl:mr-4" :class="{ 'opacity-25': form.processing }"
                                               :disabled="form.processing">
                                    {{__('Add Employee')}}
                                </PrimaryButton>
                            </div>
                        </form>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>




