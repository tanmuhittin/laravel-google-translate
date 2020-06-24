<template>
    <div>{{ $trans.get('My name is Wubadu') }}</div>
    <div>{{$t("Who let the dogs out?")}}</div>
</template>

<script>
    $trans.get('My name is Badu');
    $t("Can't touch this!");
    __('Hey there I am using Laravel');
</script>
