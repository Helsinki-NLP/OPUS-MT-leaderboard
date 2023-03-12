#!/usr/bin/env python3

from pynvml import (
    nvmlInit, nvmlDeviceGetCount, nvmlDeviceGetHandleByIndex,
    nvmlDeviceGetTotalEnergyConsumption, nvmlShutdown
)

nvmlInit()

deviceCount = nvmlDeviceGetCount()
for i in range(deviceCount):
    handle = nvmlDeviceGetHandleByIndex(i)
    energy = nvmlDeviceGetTotalEnergyConsumption(handle)
    print(f"GPU {i}: {energy} mJ")

nvmlShutdown()
